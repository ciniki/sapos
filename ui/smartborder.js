//
function ciniki_sapos_smartborder() {
	//
	// Panels
	//
	this.dayschedule = null;
	this.add = null;

	this.cb = null;
	this.toggleOptions = {'off':'Off', 'on':'On'};

	this.init = function() {
		//
		// The panel to display the Calendars, which include any business appointments
		//
		this.shipments = new M.panel('Smart Border',
			'ciniki_sapos_smartborder', 'shipments',
			'mc', 'medium mediumflex', 'sectioned', 'ciniki.sapos.smartborder.shipments');
		this.shipments.data = {};
		this.shipments.appointments = null;
		var dt = new Date();
		this.shipments.date = dt.getFullYear() + '-' + (dt.getMonth()+1) + '-' + dt.getDate();
		this.shipments.datePickerValue = function(s, d) { return this.date; }
		this.shipments.sections = {
			'datepicker':{'label':'', 'type':'datepicker', 'livesearch':'yes', 'livesearchtype':'appointments', 
				'livesearchempty':'no', 'livesearchcols':2, 'fn':'M.ciniki_sapos_smartborder.showSelectedDay',
				'hint':'Search',
				'headerValues':null,
				'noData':'No orders found',
				},
			'shipments':{'label':'Shipments', 'type':'simplegrid', 'num_cols':5,
				'headerValues':['Customer', '# Boxes', '# Pieces', 'Weight', 'Total'],
				'sortable':'yes',
				'sortTypes':['text','number','number','number','number'],
				'noData':'No shipments',
				},
			};
		this.shipments.scheduleDate = function(s, d) {
			return this.date;
		};
		this.shipments.sectionData = function(s) { return this.data[s]; }
		this.shipments.cellValue = function(s, i, j, d) {
			switch(j) {
				case 0: return d.shipment.customer_display_name;
				case 1: return d.shipment.num_boxes;
				case 2: return d.shipment.num_pieces;
				case 3: return d.shipment.weight + ' ' + d.shipment.weight_units_text;
				case 4: return d.shipment.total_amount_display;
			}
		};
		this.shipments.addClose('Back');
	}

	//
	// Arguments:
	// aG - The arguments to be parsed into args
	//
	this.start = function(cb, appPrefix, aG) {
		args = {};
		if( aG != null ) { args = eval(aG); }

		//
		// Create the app container if it doesn't exist, and clear it out
		// if it does exist.
		//
		var appContainer = M.createContainer(appPrefix, 'ciniki_sapos_smartborder', 'yes');
		if( appContainer == null ) {
			alert('App Error');
			return false;
		} 

		this.cb = cb;
		if( args.date != null ) {
			this.showShipments(cb, args.date);
		} else {
			this.showShipments(cb, null);
		}
	}

	this.showShipments = function(cb, dt) {
		if( dt != null ) { this.shipments.date = dt; }
		M.api.getJSONCb('ciniki.sapos.reportSmartBorder', 
			{'business_id':M.curBusinessID, 'start_date':this.shipments.date}, function(rsp) {
				if( rsp.stat != 'ok' ) {
					M.api.err(rsp);
					return false;
				}
				var p = M.ciniki_sapos_smartborder.shipments;
				p.data = {'shipments':rsp.shipments};
				p.refresh();
				p.show(cb);
			});
	};

	this.showSelectedDay = function(i, dt) {
		this.showShipments(null, dt);
	};
}
