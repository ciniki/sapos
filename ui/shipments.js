function ciniki_sapos_shipments() {
	this.init = function() {
		//
		// The panel to display a list of invoices that require shipping
		//
		this.invoices = new M.panel('Shipping Required',
			'ciniki_sapos_shipments', 'invoices',
			'mc', 'medium', 'sectioned', 'ciniki.sapos.shipments.invoices');
		this.invoices.data = {};
		this.invoices.sections = {
			'invoices':{'label':'', 'type':'simplegrid', 'num_cols':4,
				'sortable':'yes',
				'headerValues':['Invoice #', 'Date', 'Customer', 'Status'],
				'sortTypes':['number', 'date', 'text', 'text'],
				'noData':'Nothing to be packed',
				},
		};
		this.invoices.sectionData = function(s) {
			return this.data[s];
		};
		this.invoices.noData = function(s) {
			return this.sections[s].noData;
		};
		this.invoices.cellValue = function(s, i, j, d) {
			if( s == 'invoices' ) {
				switch(j) {
					case 0: return d.invoice.invoice_number;
					case 1: return d.invoice.invoice_date;
					case 2: return d.invoice.customer_display_name;
					case 3: return d.invoice.status_text;
				}
			}
		};
		this.invoices.rowFn = function(s, i, d) {
			if( s == 'invoices' ) {
				return 'M.startApp(\'ciniki.sapos.invoice\',null,\'M.ciniki_sapos_shipments.showInvoices();\',\'mc\',{\'invoice_id\':\'' + d.invoice.id + '\'});';
			}
		};
		this.invoices.addClose('Back');

		//
		// The panel to display the list of shipments 
		//
		this.list = new M.panel('Shipments',
			'ciniki_sapos_shipments', 'list',
			'mc', 'medium', 'sectioned', 'ciniki.sapos.shipments.list');
		this.list.data = {};
		this.list.sections = {
			'shipments':{'label':'', 'type':'simplegrid', 'num_cols':4,
				'sortable':'yes',
				'headerValues':['Inv #-Shipment #', 'Date', 'Customer', 'Status'],
				'sortTypes':['number', 'date', 'text', 'text'],
				'noData':'Nothing to be shipped',
				},
		};
		this.list.sectionData = function(s) {
			return this.data[s];
		};
		this.list.noData = function(s) {
			return this.sections[s].noData;
		};
		this.list.cellValue = function(s, i, j, d) {
			if( s == 'shipments' ) {
				switch(j) {
					case 0: return d.shipment.packing_slip_number;
					case 1: return d.shipment.invoice_date;
					case 2: return d.shipment.customer_display_name;
					case 3: return d.shipment.status_text;
				}
			}
		};
		this.list.rowFn = function(s, i, d) {
			if( s == 'shipments' ) {
				return 'M.startApp(\'ciniki.sapos.shipment\',null,\'M.ciniki_sapos_shipments.showShipments();\',\'mc\',{\'shipment_id\':\'' + d.shipment.id + '\'});';
			}
		};
		this.list.addClose('Back');
	};

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
		var appContainer = M.createContainer(appPrefix, 'ciniki_sapos_shipments', 'yes');
		if( appContainer == null ) {
			alert('App Error');
			return false;
		} 

		if( args.list != null ) {
			if( args.list == 'packlist' ) {
				this.showInvoices(cb, args.list);
			}
			else if( args.list == 'pendship' ) {
				this.showShipments(cb, args.list);
			}
			else if( args.list == 'onhold' ) {
				this.showInvoices(cb, args.list);
			}
			else if( args.list == 'backordered' ) {
				this.showInvoices(cb, args.list);
			}
		}
	};

	this.showInvoices = function(cb, list) {
		if( list != null ) { this.invoices._list = list; }
		if( this.invoices._list == 'packlist' ) {
			M.api.getJSONCb('ciniki.sapos.invoiceList', {'business_id':M.curBusinessID,
				'shipping_status':'packlist', 'sort':'invoice_date'}, function(rsp) {
					if( rsp.stat != 'ok' ) {
						M.api.err(rsp);
						return false;
					}
					var p = M.ciniki_sapos_shipments.invoices;
					p.data.invoices = rsp.invoices;
					p.refresh();
					p.show(cb);
				});
		} else if( this.invoices._list == 'onhold' ) {
			M.api.getJSONCb('ciniki.sapos.invoiceList', {'business_id':M.curBusinessID,
				'status':'15', 'sort':'invoice_date'}, function(rsp) {
					if( rsp.stat != 'ok' ) {
						M.api.err(rsp);
						return false;
					}
					var p = M.ciniki_sapos_shipments.invoices;
					p.data.invoices = rsp.invoices;
					p.refresh();
					p.show(cb);
				});
		} else if( this.invoices._list == 'backordered' ) {
			M.api.getJSONCb('ciniki.sapos.invoiceList', {'business_id':M.curBusinessID,
				'shipping_status':'backordered', 'sort':'invoice_date'}, function(rsp) {
					if( rsp.stat != 'ok' ) {
						M.api.err(rsp);
						return false;
					}
					var p = M.ciniki_sapos_shipments.invoices;
					p.data.invoices = rsp.invoices;
					p.refresh();
					p.show(cb);
				});
		}
	};

	this.showShipments = function(cb, list) {
		if( list != null ) { this.list._list = list; }
		if( this.list._list == 'pendship' ) {
			M.api.getJSONCb('ciniki.sapos.shipmentList', {'business_id':M.curBusinessID,
				'status':'20', 'sort':'invoice_date'}, function(rsp) {
					if( rsp.stat != 'ok' ) {
						M.api.err(rsp);
						return false;
					}
					var p = M.ciniki_sapos_shipments.list;
					p.data.shipments = rsp.shipments;
					p.refresh();
					p.show(cb);
				});
		}
	};
}
