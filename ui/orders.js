//
function ciniki_sapos_orders() {
	this.init = function() {
		//
		// The menu panel
		//
		this.menu = new M.panel('Orders',
			'ciniki_sapos_orders', 'menu',
			'mc', 'medium mediumflex', 'sectioned', 'ciniki.sapos.main.menu');
		this.menu.data = {};
		this.menu.sections = {
			'order_search':{'label':'', 'type':'livesearchgrid', 'livesearchcols':5, 
				'headerValues':['Order #','Date','Customer','Amount','Status'],
				'hint':'Search order # or customer name', 
				'noData':'No orders found',
				},
			'orders':{'label':'', 'list':{
				'packlist':{'label':'Packing Required', 'fn':'M.startApp(\'ciniki.sapos.shipments\',null,\'M.ciniki_sapos_orders.showMenu();\',\'mc\',{\'list\':\'packlist\'});'},
				'pendship':{'label':'Shipping Required', 'fn':'M.startApp(\'ciniki.sapos.shipments\',null,\'M.ciniki_sapos_orders.showMenu();\',\'mc\',{\'list\':\'pendship\'});'},
				'backordered':{'label':'Backordered Orders', 'fn':'M.startApp(\'ciniki.sapos.shipments\',null,\'M.ciniki_sapos_orders.showMenu();\',\'mc\',{\'list\':\'backordered\'});'},
				}},
			'carts':{'label':'', 'list':{
				'opencarts':{'label':'Open Shopping Carts', 'fn':'M.startApp(\'ciniki.sapos.carts\',null,\'M.ciniki_sapos_orders.showMenu();\',\'mc\',{\'list\':\'opencarts\'});'},
				}},
			'reports':{'label':'', 'list':{
				'smartborder':{'label':'Smart Border', 'fn':'M.startApp(\'ciniki.sapos.smartborder\',null,\'M.ciniki_sapos_orders.showMenu();\');'},
				'mwexport':{'label':'Moneyworks Export', 'fn':'M.startApp(\'ciniki.sapos.mwexport\',null,\'M.ciniki_sapos_orders.showMenu();\');'},
				}},
			'invoices':{'label':'Recent Orders', 'type':'simplegrid', 'num_cols':5,
				'headerValues':['Invoice #','Date','Customer','Amount','Status'],
				'noData':'No Invoices',
//				'addTxt':'More',
//				'addFn':'M.startApp(\'ciniki.sapos.invoices\',null,\'M.ciniki_sapos_orders.showMenu();\',\'mc\',{\'invoice_type\':\'40\'});',
				},
			};
		this.menu.liveSearchCb = function(s, i, v) {
			if( s == 'order_search' && v != '' ) {
				M.api.getJSONBgCb('ciniki.sapos.invoiceSearch', {'business_id':M.curBusinessID,
					'start_needle':v, 'invoice_type':'40', 'sort':'reverse', 'limit':'10'}, function(rsp) {
						M.ciniki_sapos_orders.menu.liveSearchShow('order_search',null,M.gE(M.ciniki_sapos_orders.menu.panelUID + '_' + s), rsp.invoices);
					});
			}
		};
		this.menu.liveSearchResultValue = function(s, f, i, j, d) {
			if( s == 'order_search' ) { 
				switch (j) {
					case 0: return d.invoice.invoice_number;
					case 1: return d.invoice.invoice_date;
					case 2: return d.invoice.customer_display_name;
					case 3: return d.invoice.total_amount_display;
					case 4: return d.invoice.status_text;
				}
			}
			return '';
		};
		this.menu.liveSearchResultRowFn = function(s, f, i, j, d) {
			if( s == 'order_search' ) {
				return 'M.startApp(\'ciniki.sapos.invoice\',null,\'M.ciniki_sapos_orders.showMenu();\',\'mc\',{\'invoice_id\':\'' + d.invoice.id + '\'});';
			}
		};
		this.menu.sectionData = function(s) {
			if( s == 'invoices' ) { return this.data[s]; }
			return this.sections[s].list;
		};
		this.menu.noData = function(s) {
			return this.sections[s].noData;
		};
		this.menu.listCount = function(s, i, d) {
			if( i == 'packlist' ) {
				if( this.data.stats.invoices.typestatus['40.30'] != null ) {
					return this.data.stats.invoices.typestatus['40.30'];
				}
				return '0';
			}
			if( i == 'pendship' ) {
				if( this.data.stats.shipments.status['20'] != null ) {
					return this.data.stats.shipments.status['20'];
				}
				return '0';
			}
			if( i == 'opencarts' ) {
				if( this.data.stats.invoices.typestatus['20.10'] != null ) {
					return this.data.stats.invoices.typestatus['20.10'];
				}
				return '0';
			}
			return '';
		};
		this.menu.cellValue = function(s, i, j, d) {
			if( s == 'invoices' ) {
				switch(j) {
					case 0: return d.invoice.invoice_number;
					case 1: return d.invoice.invoice_date;
					case 2: return d.invoice.customer_display_name;
					case 3: return d.invoice.total_amount_display;
					case 4: return d.invoice.status_text;
				}
			}
		};
		this.menu.rowFn = function(s, i, d) {
			if( s == 'invoices' ) {
				return 'M.startApp(\'ciniki.sapos.invoice\',null,\'M.ciniki_sapos_orders.showMenu();\',\'mc\',{\'invoice_id\':\'' + d.invoice.id + '\'});';
			}
		};
		this.menu.addButton('add', 'Order', 'M.startApp(\'ciniki.sapos.invoice\',null,\'M.ciniki_sapos_orders.showMenu();\',\'mc\',{\'customer_id\':\'0\',\'invoice_type\':\'40\'});', 'add');
		this.menu.addClose('Back');
	};

	//
	// Arguments:
	// aG - The arguments to be parsed into args
	//
	this.start = function(cb, appPrefix, aG) {
		args = {};
		if( aG != null ) {
			args = eval(aG);
		}

		//
		// Create the app container if it doesn't exist, and clear it out
		// if it does exist.
		//
		var appContainer = M.createContainer(appPrefix, 'ciniki_sapos_orders', 'yes');
		if( appContainer == null ) {
			alert('App Error');
			return false;
		} 

		// Orders enabled
		if( (M.curBusiness.modules['ciniki.sapos'].flags&0x20) > 0 ) {
			this.menu.sections.reports.visible = 'yes';
		} else {
			this.menu.sections.reports.visible = 'no';
		}
		// Carts enabled
		if( (M.curBusiness.modules['ciniki.sapos'].flags&0x08) > 0 ) {
			this.menu.sections.carts.visible = 'yes';
		} else {
			this.menu.sections.carts.visible = 'no';
		}
		this.showMenu(cb);
	};

	//
	// Grab the stats for the business from the database and present the list of orders.
	//
	this.showMenu = function(cb) {
		M.api.getJSONCb('ciniki.sapos.latest', {'business_id':M.curBusinessID,
			'limit':'10', 'sort':'latest', 'type':'40', 'stats':'yes'}, function(rsp) {
				if( rsp.stat != 'ok' ) {
					M.api.err(rsp);
					return false;
				}
				var p = M.ciniki_sapos_orders.menu;
				p.data.invoices = rsp.invoices;
				p.data.stats = rsp.stats;
				p.sections.order_search.visible = (rsp.invoices.length > 0)?'yes':'no';
				p.sections.invoices.visible = (rsp.invoices.length > 0)?'yes':'no';
				p.refresh();
				p.show(cb);
			});
	};
}
