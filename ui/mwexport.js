//
function ciniki_sapos_mwexport() {
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
		this.shipments = new M.panel('Export',
			'ciniki_sapos_mwexport', 'shipments',
			'mc', 'xlarge', 'sectioned', 'ciniki.sapos.mwexport.shipments');
		this.shipments.data = {};
		this.shipments.appointments = null;
		var dt = new Date();
		this.shipments.date = dt.getFullYear() + '-' + (dt.getMonth()+1) + '-' + dt.getDate();
		this.shipments.datePickerValue = function(s, d) { return this.date; }
		this.shipments.sections = {
			'datepicker':{'label':'', 'fields':{
				'start_date':{'label':'Start', 'type':'date', 
					'fn':'M.ciniki_sapos_mwexport.shipments.updateStartDate'},
				'end_date':{'label':'End', 'type':'date',
					'fn':'M.ciniki_sapos_mwexport.shipments.updateEndDate'},
				}},
			'_buttons':{'label':'', 'buttons':{
//				'update':{'label':'Update', 'fn':'M.ciniki_sapos_mwexport.showShipments();'},
//				'downloadtab':{'label':'Download Tab Delimited', 'fn':'M.ciniki_sapos_mwexport.showShipments(null,null,\'tab\');'},
				'downloadexcel':{'label':'Download Excel', 'fn':'M.ciniki_sapos_mwexport.showShipments(null,null,\'excel\');'},
				}},
			'items':{'label':'Shipment Items', 'type':'simplegrid', 'num_cols':18,
				'headerValues':['INV #', 'Ship #', 'Order Date', 'Ship Date', 'Invoice Status', 
					'ID', 'Customer', 'Rep', 
					'Shipper', 'Tracking #', 'Freight Amount', 
					'Code', 'Description', 'Quantity',
					'Price Code', 'Unit Amount', 'Total', 'Tax code', 'Invoice Total'],
				'sortable':'yes',
//				'sortTypes':['number','number','date','date','text','text','text','number'],
				'noData':'No shipments',
				},
			};
		this.shipments.updateStartDate = function(field, date) {
			this.setFromCalendar(field, date);
			M.ciniki_sapos_mwexport.showShipments();
		};
		this.shipments.updateEndDate = function(field, date) {
			this.setFromCalendar(field, date);
			M.ciniki_sapos_mwexport.showShipments();
		};
		this.shipments.scheduleDate = function(s, d) {
			return this.date;
		};
		this.shipments.sectionData = function(s) { return this.data[s]; }
		this.shipments.fieldValue = function(s, i, d) { return this.data[i]; }
		this.shipments.cellValue = function(s, i, j, d) {
			switch(j) {
				case 0: return d.item.invoice_number;
				case 1: return d.item.shipment_number;
				case 2: return d.item.invoice_date;
				case 3: return d.item.ship_date;
				case 4: return d.item.status_text;
				case 5: return d.item.customer_eid;
				case 6: return d.item.customer_display_name;
				case 7: return d.item.salesrep_display_name;
				case 8: return d.item.shipping_company;
				case 9: return d.item.tracking_number;
				case 10: return d.item.freight_amount_display;
				case 11: return d.item.code;
				case 12: return d.item.description;
				case 13: return d.item.shipment_quantity;
				case 14: return d.item.pricepoint_code;
				case 15: return d.item.unit_amount_display;
				case 16: return d.item.total_amount_display;
				case 17: return d.item.tax_location_code;
				case 18: return d.item.invoice_total_amount;
			}
		};
		this.shipments.rowFn = function(s, i, d) {
			return 'M.startApp(\'ciniki.sapos.invoice\',null,\'M.ciniki_sapos_mwexport.showShipments();\',\'mc\',{\'invoice_id\':\'' + d.item.invoice_id + '\'});';
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
		var appContainer = M.createContainer(appPrefix, 'ciniki_sapos_mwexport', 'yes');
		if( appContainer == null ) {
			alert('App Error');
			return false;
		} 

		this.cb = cb;
		if( args.date != null ) {
			this.showShipments(cb, args.date);
		} else {
			var dt = new Date();
			this.showShipments(cb, M.dateFormat(dt));
		}
	}

	this.showShipments = function(cb, sd, format) {
		if( sd != null ) { 
			this.shipments.data.start_date = sd;
			this.shipments.data.end_date = '';
		} else {
			this.shipments.data.start_date = this.shipments.formFieldValue(this.shipments.sections.datepicker.fields.start_date, 'start_date');
			this.shipments.data.end_date = this.shipments.formFieldValue(this.shipments.sections.datepicker.fields.end_date, 'end_date');
		}
		var args = {'business_id':M.curBusinessID, 
			'start_date':this.shipments.data.start_date,
			'end_date':this.shipments.data.end_date,
			};
		if( format != null ) {
			args['output'] = format;
			window.open(M.api.getUploadURL('ciniki.sapos.reportMWExport', args));
			delete(args['output']);
		} else {
			M.api.getJSONCb('ciniki.sapos.reportMWExport', args, function(rsp) {
				if( rsp.stat != 'ok' ) {
					M.api.err(rsp);
					return false;
				}
				var p = M.ciniki_sapos_mwexport.shipments;
				p.data.items = rsp.items;
				p.refresh();
				p.show(cb);
			});
		}
	};
}
