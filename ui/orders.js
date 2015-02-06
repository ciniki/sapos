//
function ciniki_sapos_orders() {
	this.init = function() {
		//
		// The menu panel
		//
		this.menu = new M.panel('Orders',
			'ciniki_sapos_orders', 'menu',
			'mc', 'medium mediumaside', 'sectioned', 'ciniki.sapos.main.menu');
		this.menu.data = {};
		this.menu.sections = {
			'orders':{'label':'', 'aside':'yes', 'list':{
				'recent':{'label':'Recent Orders', 'fn':'M.ciniki_sapos_orders.showMenu(null,\'recent\',\'Recent Orders\');'},
				'packlist':{'label':'Packing Required', 'fn':'M.ciniki_sapos_orders.showMenu(null,\'packlist\',\'Packing Required\');'},
				'pendship':{'label':'Shipping Required', 'fn':'M.ciniki_sapos_orders.showMenu(null,\'pendship\',\'Shipping Required\');'},
				'incomplete':{'label':'Incomplete', 'fn':'M.ciniki_sapos_orders.showMenu(null,\'incomplete\',\'Incomplete Orders\');'},
				'onhold':{'label':'On Hold', 'fn':'M.ciniki_sapos_orders.showMenu(null,\'onhold\',\'On Hold\');'},
				'backordered':{'label':'Backordered', 'fn':'M.ciniki_sapos_orders.showMenu(null,\'backordered\',\'Backordered\');'},
//				'packlist':{'label':'Packing Required', 'fn':'M.startApp(\'ciniki.sapos.shipments\',null,\'M.ciniki_sapos_orders.showMenu();\',\'mc\',{\'list\':\'packlist\'});'},
//				'pendship':{'label':'Shipping Required', 'fn':'M.startApp(\'ciniki.sapos.shipments\',null,\'M.ciniki_sapos_orders.showMenu();\',\'mc\',{\'list\':\'pendship\'});'},
//				'packlist':{'label':'Packing Required', 'fn':'M.ciniki_sapos_orders.showInvoices(\'M.ciniki_sapos_orders.showMenu();\',\'packlist\',\'Packing Required\');'},
//				'pendship':{'label':'Shipping Required', 'fn':'M.startApp(\'ciniki.sapos.shipments\',null,\'M.ciniki_sapos_orders.showMenu();\',\'mc\',{\'list\':\'pendship\'});'},
//				'pendship':{'label':'Shipping Required', 'fn':'M.startApp(\'ciniki.sapos.shipments\',null,\'M.ciniki_sapos_orders.showMenu();\',\'mc\',{\'list\':\'pendship\'});'},
//				'onhold':{'label':'On Hold', 'fn':'M.startApp(\'ciniki.sapos.shipments\',null,\'M.ciniki_sapos_orders.showMenu();\',\'mc\',{\'list\':\'onhold\'});'},
//				'incomplete':{'label':'Incomplete', 'fn':'M.ciniki_sapos_orders.showInvoices(\'M.ciniki_sapos_orders.showMenu();\',\'incomplete\',\'Incomplete Orders\');'},
//				'onhold':{'label':'On Hold', 'fn':'M.ciniki_sapos_orders.showInvoices(\'M.ciniki_sapos_orders.showMenu();\',\'onhold\',\'On Hold\');'},
//				'backordered':{'label':'Backordered', 'fn':'M.ciniki_sapos_orders.showInvoices(\'M.ciniki_sapos_orders.showMenu();\',\'backordered\',\'Backordered\');'},
				}},
			'carts':{'label':'', 'aside':'yes', 'list':{
//				'opencarts':{'label':'Open Shopping Carts', 'fn':'M.startApp(\'ciniki.sapos.carts\',null,\'M.ciniki_sapos_orders.showMenu();\',\'mc\',{\'list\':\'opencarts\'});'},
				'opencarts':{'label':'Open Shopping Carts', 'fn':'M.ciniki_sapos_orders.showMenu(null,\'opencarts\',\'Shopping Carts\');'},
				}},
			'reports':{'label':'', 'aside':'yes', 'list':{
				'smartborder':{'label':'Smart Border', 'fn':'M.startApp(\'ciniki.sapos.smartborder\',null,\'M.ciniki_sapos_orders.showMenu();\');'},
				'mwexport':{'label':'Moneyworks Export', 'fn':'M.startApp(\'ciniki.sapos.mwexport\',null,\'M.ciniki_sapos_orders.showMenu();\');'},
				'backordereditems':{'label':'Backordered Items', 'fn':'M.startApp(\'ciniki.sapos.backordereditems\',null,\'M.ciniki_sapos_orders.showMenu();\');'},
				'more':{'label':'All Orders', 'fn':'M.ciniki_sapos_orders.showOrders(\'M.ciniki_sapos_orders.showMenu();\');'},
				}},
			'order_search':{'label':'', 'hidelabel':'yes', 'type':'livesearchgrid', 'livesearchcols':4, 
				'headerValues':['Order #','Date','Customer','Status'],
				'hint':'Search order # or customer name', 
				'noData':'No orders found',
				},
			'shipments':{'label':'Shipping Required', 'type':'simplegrid', 'num_cols':4,
				'headerValues':['Order #','Date','Customer','Status'],
				'sortable':'yes',
				'sortTypes':['number','date','text','number','text'],
				'noData':'No orders',
				},
			'invoices':{'label':'Recent Orders', 'type':'simplegrid', 'num_cols':4,
				'headerValues':['Order #','Date','Customer','Status'],
				'sortable':'yes',
				'sortTypes':['number','date','text','number','text'],
				'noData':'No orders',
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
//					case 3: return d.invoice.total_amount_display;
					case 3: return d.invoice.status_text;
				}
			}
			return '';
		};
		this.menu.liveSearchSubmitFn = function(s, value) {
			M.ciniki_sapos_orders.showInvoices('M.ciniki_sapos_orders.showMenu();', '_search', 'Search Results', value);
		};
		this.menu.liveSearchResultRowFn = function(s, f, i, j, d) {
			if( s == 'order_search' ) {
				return 'M.startApp(\'ciniki.sapos.invoice\',null,\'M.ciniki_sapos_orders.showMenu();\',\'mc\',{\'invoice_id\':\'' + d.invoice.id + '\'});';
			}
		};
		this.menu.sectionData = function(s) {
			if( s == 'invoices' ) { return this.data[s]; }
			if( s == 'shipments' ) { return this.data[s]; }
			return this.sections[s].list;
		};
		this.menu.noData = function(s) {
			return this.sections[s].noData;
		};
		this.menu.listCount = function(s, i, d) {
			if( i == 'packlist' ) {
				if( this.data.stats.shipping.status['available'] != null ) {
					return this.data.stats.shipping.status['available'];
				}
				return '0';
			}
			if( i == 'pendship' ) {
				if( this.data.stats.shipments.status['20'] != null ) {
					return this.data.stats.shipments.status['20'];
				}
				return '0';
			}
			if( i == 'incomplete' ) {
				if( this.data.stats.invoices.typestatus['40.10'] != null ) {
					return this.data.stats.invoices.typestatus['40.10'];
				}
				return '0';
			}
			if( i == 'onhold' ) {
				if( this.data.stats.invoices.typestatus['40.15'] != null ) {
					return this.data.stats.invoices.typestatus['40.15'];
				}
				return '0';
			}
			if( i == 'backordered' ) {
				if( this.data.stats.shipping.status['backordered'] != null ) {
					return this.data.stats.shipping.status['backordered'];
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
//					case 3: return d.invoice.total_amount_display;
					case 3: return d.invoice.status_text;
				}
			}
			else if( s == 'shipments' ) {
				switch(j) {
					case 0: return d.shipment.packing_slip_number;
					case 1: return d.shipment.invoice_date;
					case 2: return d.shipment.customer_display_name;
					case 3: return d.shipment.status_text;
				}
			}
		};
		this.menu.cellFn = function(s, i, j, d) {
			if( s == 'shipments' && j == 0 ) {
				return 'event.stopPropagation(); M.startApp(\'ciniki.sapos.invoice\',null,\'M.ciniki_sapos_orders.showMenu();\',\'mc\',{\'invoice_id\':\'' + d.shipment.invoice_id + '\'});';
			}
		};
		this.menu.rowFn = function(s, i, d) {
			if( s == 'invoices' ) {
				return 'M.startApp(\'ciniki.sapos.invoice\',null,\'M.ciniki_sapos_orders.showMenu();\',\'mc\',{\'invoice_id\':\'' + d.invoice.id + '\'});';
			}
			else if( s == 'shipments' ) {
				return 'M.startApp(\'ciniki.sapos.shipment\',null,\'M.ciniki_sapos_orders.showMenu();\',\'mc\',{\'shipment_id\':\'' + d.shipment.id + '\'});';
			}
		};
		this.menu.addButton('add', 'Order', 'M.startApp(\'ciniki.sapos.invoice\',null,\'M.ciniki_sapos_orders.showMenu();\',\'mc\',{\'customer_id\':\'0\',\'invoice_type\':\'40\'});', 'add');
		this.menu.addClose('Back');

		//
		// The panel to display a list of invoices 
		//
		this.invoices = new M.panel('Orders',
			'ciniki_sapos_orders', 'invoices',
			'mc', 'medium mediumflex', 'sectioned', 'ciniki.sapos.orders.invoices');
		this.invoices.data = {};
		this.invoices.sections = {
			'invoices':{'label':'', 'type':'simplegrid', 'num_cols':4,
				'sortable':'yes',
				'headerValues':['Order #', 'Date', 'Customer', 'Status'],
				'sortTypes':['number', 'date', 'text', 'text'],
				'noData':'No orders found',
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
				return 'M.startApp(\'ciniki.sapos.invoice\',null,\'M.ciniki_sapos_orders.showInvoices();\',\'mc\',{\'invoice_id\':\'' + d.invoice.id + '\',\'list\':M.ciniki_sapos_orders.invoices.data.invoices});';
			}
		};
		this.invoices.addClose('Back');

		//
		// The panel to show all the orders
		//
		this.orders = new M.panel('Orders',
			'ciniki_sapos_orders', 'orders',
			'mc', 'large', 'sectioned', 'ciniki.sapos.orders.orders');
		this.orders.year = 0;
		this.orders.month = 0;
		this.orders.order_status = 0;
		this.orders.data = {};
		this.orders.sections = {
			'years':{'label':'', 'type':'paneltabs', 'selected':'', 'tabs':{}},
			'months':{'label':'', 'visible':'yes', 'type':'paneltabs', 'selected':'0', 'tabs':{
				'0':{'label':'All', 'fn':'M.ciniki_sapos_orders.showOrders(null,null,0);'},
				'1':{'label':'Jan', 'fn':'M.ciniki_sapos_orders.showOrders(null,null,1);'},
				'2':{'label':'Feb', 'fn':'M.ciniki_sapos_orders.showOrders(null,null,2);'},
				'3':{'label':'Mar', 'fn':'M.ciniki_sapos_orders.showOrders(null,null,3);'},
				'4':{'label':'Apr', 'fn':'M.ciniki_sapos_orders.showOrders(null,null,4);'},
				'5':{'label':'May', 'fn':'M.ciniki_sapos_orders.showOrders(null,null,5);'},
				'6':{'label':'Jun', 'fn':'M.ciniki_sapos_orders.showOrders(null,null,6);'},
				'7':{'label':'Jul', 'fn':'M.ciniki_sapos_orders.showOrders(null,null,7);'},
				'8':{'label':'Aug', 'fn':'M.ciniki_sapos_orders.showOrders(null,null,8);'},
				'9':{'label':'Sep', 'fn':'M.ciniki_sapos_orders.showOrders(null,null,9);'},
				'10':{'label':'Oct', 'fn':'M.ciniki_sapos_orders.showOrders(null,null,10);'},
				'11':{'label':'Nov', 'fn':'M.ciniki_sapos_orders.showOrders(null,null,11);'},
				'12':{'label':'Dec', 'fn':'M.ciniki_sapos_orders.showOrders(null,null,12);'},
				}},
			'statuses':{'label':'', 'visible':'yes', 'type':'paneltabs', 'selected':'0', 'tabs':{
				'0':{'label':'All', 'fn':'M.ciniki_sapos_orders.showOrders(null,null,null,0);'},
				'10':{'label':'Incomplete', 'fn':'M.ciniki_sapos_orders.showOrders(null,null,null,10);'},
				'15':{'label':'On Hold', 'fn':'M.ciniki_sapos_orders.showOrders(null,null,null,15);'},
				'30':{'label':'Pending Shipping', 'fn':'M.ciniki_sapos_orders.showOrders(null,null,null,30);'},
				'50':{'label':'Fulfilled', 'fn':'M.ciniki_sapos_orders.showOrders(null,null,null,50);'},
				}},
			'invoices':{'label':'', 'type':'simplegrid', 'num_cols':6,
				'sortable':'yes',
				'headerValues':['Invoice #', 'Ordered', 'Shipped', 'Customer', 'Amount', 'Status'],
				'sortTypes':['number', 'date', 'date', 'text', 'number', 'text'],
				'noData':'No Orders Found',
				},
//			'_buttons':{'label':'', 'buttons':{
//				'excel':{'label':'Download Excel', 'fn':'M.ciniki_sapos_orders.downloadExcel();'},
//				}},
		};
		this.orders.footerValue = function(s, i, d) {
			if( this.data.totals != null ) {
				switch(i) {
					case 0: return this.data.totals.num_orders;
					case 1: return '';
					case 2: return '';
					case 3: return '';
					case 4: return this.data.totals.total_amount;
					case 5: return '';
				}
			}
		};
		this.orders.footerClass = function(s, i, d) {
			if( i == 4 ) { return 'alignright'; }
			return '';
		};
		this.orders.sectionData = function(s) {
//			if( s == 'totals' ) { return this.sections[s].list; }
			return this.data[s];
		};
		this.orders.noData = function(s) {
			return this.sections[s].noData;
		};
		this.orders.listLabel = function(s, i, d) {
			return d.label;
		};
		this.orders.listValue = function(s, i, d) {
			return this.data.totals[i];
		};
		this.orders.cellValue = function(s, i, j, d) {
			if( s == 'invoices' ) {
				switch(j) {
					case 0: return d.invoice.invoice_number;
					case 1: return d.invoice.invoice_date;
					case 2: return d.invoice.shipment_dates;
					case 3: return d.invoice.customer_display_name;
					case 4: return d.invoice.total_amount_display;
					case 5: return d.invoice.status_text;
				}
			}
		};
		
		this.orders.rowFn = function(s, i, d) {
			if( s == 'invoices' ) {
				return 'M.startApp(\'ciniki.sapos.invoice\',null,\'M.ciniki_sapos_orders.showOrders();\',\'mc\',{\'invoice_id\':\'' + d.invoice.id + '\'});';
			}
		};
		this.orders.addClose('Back');
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

		this.orders.sections.years.tabs = {};
		this.orders.sections.years.visible = 'no';
		this.showMenu(cb, 'recent', 'Recent Orders');
	};

	//
	// Grab the stats for the business from the database and present the list of orders.
	//
	this.showMenu = function(cb, list, title) {
		if( cb != null ) { this.menu.cb = cb; }
		if( list != null ) { this.menu.invoice_list = list; }
		if( title != null ) { this.menu.sections.invoices.label = title; }
		this.menu.sections.invoices.visible = 'yes';
		this.menu.sections.shipments.visible = 'no';
		if( this.menu.invoice_list == 'recent' ) {
			M.api.getJSONCb('ciniki.sapos.latest', {'business_id':M.curBusinessID,
				'limit':'25', 'sort':'latest', 'type':'40', 'stats':'yes'}, 
				M.ciniki_sapos_orders.showMenuFinish);
		} else if( this.menu.invoice_list == 'packlist' ) {
			M.api.getJSONCb('ciniki.sapos.invoiceList', {'business_id':M.curBusinessID,
				'shipping_status':'packlist', 'sort':'invoice_date', 'stats':'yes'}, 
				M.ciniki_sapos_orders.showMenuFinish);
		} else if( this.menu.invoice_list == 'pendship' ) {
			this.menu.sections.invoices.visible = 'no';
			this.menu.sections.shipments.visible = 'yes';
			M.api.getJSONCb('ciniki.sapos.shipmentList', {'business_id':M.curBusinessID,
				'status':'20', 'sort':'invoice_date', 'stats':'yes'}, 
				M.ciniki_sapos_orders.showMenuFinish);
		} else if( this.menu.invoice_list == 'incomplete' ) {
			M.api.getJSONCb('ciniki.sapos.invoiceList', {'business_id':M.curBusinessID,
				'status':'10', 'type':'40', 'sort':'invoice_date', 'stats':'yes'}, 
				M.ciniki_sapos_orders.showMenuFinish);
		} else if( this.menu.invoice_list == 'onhold' ) {
			M.api.getJSONCb('ciniki.sapos.invoiceList', {'business_id':M.curBusinessID,
				'status':'15', 'type':'40', 'sort':'invoice_date', 'stats':'yes'}, 
				M.ciniki_sapos_orders.showMenuFinish);
		} else if( this.menu.invoice_list == 'backordered' ) {
			M.api.getJSONCb('ciniki.sapos.invoiceList', {'business_id':M.curBusinessID,
				'shipping_status':'backordered', 'type':'40', 'sort':'invoice_date', 'stats':'yes'}, 
				M.ciniki_sapos_orders.showMenuFinish);
		} else if( this.menu.invoice_list == 'opencarts' ) {
			M.api.getJSONCb('ciniki.sapos.invoiceList', {'business_id':M.curBusinessID,
				'status':'10', 'type':'20', 'sort':'invoice_date_desc', 'stats':'yes'}, 
				M.ciniki_sapos_orders.showMenuFinish);
		}
	};

	this.showMenuFinish = function(rsp) {
		if( rsp.stat != 'ok' ) {
			M.api.err(rsp);
			return false;
		}
		var p = M.ciniki_sapos_orders.menu;
		p.data.shipments = rsp.shipments;
		p.data.invoices = rsp.invoices;
		if( rsp.stats.min_invoice_date_year != null && rsp.stats.min_invoice_date_year > 0 ) {
			var year = new Date().getFullYear();
			M.ciniki_sapos_orders.orders.year = year;
			M.ciniki_sapos_orders.orders.sections.years.selected = year;
		}
		p.data.stats = rsp.stats;
		p.refresh();
		p.show();
	};

	this.showInvoices = function(cb, list, title, search_str) {
		if( list != null ) { this.invoices._list = list; }
		if( title != null ) { this.invoices.title = title; }
		if( search_str != null ) { this.invoices.search_str = search_str; }
		if( this.invoices._list == 'packlist' ) {
			M.api.getJSONCb('ciniki.sapos.invoiceList', {'business_id':M.curBusinessID,
				'shipping_status':'packlist', 'sort':'invoice_date'}, function(rsp) {
					if( rsp.stat != 'ok' ) {
						M.api.err(rsp);
						return false;
					}
					var p = M.ciniki_sapos_orders.invoices;
					p.data.invoices = rsp.invoices;
					p.refresh();
					p.show(cb);
				});
		} else if( this.invoices._list == 'incomplete' ) {
			M.api.getJSONCb('ciniki.sapos.invoiceList', {'business_id':M.curBusinessID,
				'status':'10', 'type':'40', 'sort':'invoice_date'}, function(rsp) {
					if( rsp.stat != 'ok' ) {
						M.api.err(rsp);
						return false;
					}
					var p = M.ciniki_sapos_orders.invoices;
					p.data.invoices = rsp.invoices;
					p.refresh();
					p.show(cb);
				});
		} else if( this.invoices._list == 'onhold' ) {
			M.api.getJSONCb('ciniki.sapos.invoiceList', {'business_id':M.curBusinessID,
				'status':'15', 'type':'40', 'sort':'invoice_date'}, function(rsp) {
					if( rsp.stat != 'ok' ) {
						M.api.err(rsp);
						return false;
					}
					var p = M.ciniki_sapos_orders.invoices;
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
					var p = M.ciniki_sapos_orders.invoices;
					p.data.invoices = rsp.invoices;
					p.refresh();
					p.show(cb);
				});
		} else if( this.invoices._list = '_search' ) {
			M.api.getJSONCb('ciniki.sapos.invoiceSearch', {'business_id':M.curBusinessID,
				'invoice_type':'40', 'sort':'reverse', 'start_needle':this.invoices.search_str}, function(rsp) {
					if( rsp.stat != 'ok' ) {
						M.api.err(rsp);
						return false;
					}
					var p = M.ciniki_sapos_orders.invoices;
					p.data.invoices = rsp.invoices;
					p.refresh();
					p.show(cb);
				});
		}
	};

	this.showOrders = function(cb, year, month, status) {
		if( year != null ) {
			this.orders.year = year;
			this.orders.sections.years.selected = year;
		}
		if( month != null ) {
			this.orders.month = month;
			this.orders.sections.months.selected = month;
		}
		if( status != null ) {
			this.orders.order_status = status;
			this.orders.sections.statuses.selected = status;
		}
//		this.orders.sections.months.visible = (this.orders.month>0)?'yes':'yes';
		M.api.getJSONCb('ciniki.sapos.invoiceList', {'business_id':M.curBusinessID,
			'year':this.orders.year, 'month':this.orders.month,
			'status':this.orders.order_status, 'type':'40', 'shipments':'yes', 
			'sort':'invoice_date', 'stats':'yes'}, function(rsp) {
				if( rsp.stat != 'ok' ) {
					M.api.err(rsp);
					return false;
				}
				var p = M.ciniki_sapos_orders.orders;
				p.data.invoices = rsp.invoices;
				p.data.totals = rsp.totals;
				if( rsp.stats.min_invoice_date_year != null && rsp.stats.min_invoice_date_year > 0 ) {
					var year = new Date().getFullYear();
					M.ciniki_sapos_orders.orders.tabs = {};
					for(var i=rsp.stats.min_invoice_date_year;i<=year;i++) {
						M.ciniki_sapos_orders.orders.sections.years.tabs[i] = {'label':i, 'fn':'M.ciniki_sapos_invoices.showOrders(null,' + i + ',null);'};
					}
					M.ciniki_sapos_orders.orders.sections.years.visible = 'yes';
					M.ciniki_sapos_orders.orders.year = year;
					M.ciniki_sapos_orders.orders.sections.years.selected = year;
				}
//				p.sections._buttons.buttons.excel.visible=(rsp.invoices.length>0)?'yes':'no';
//				p.sections.invoices.visible = (rsp.invoices.length > 0)?'yes':'no';
				p.refresh();
				p.show(cb);
			});
	};

	this.downloadExcel = function() {
		var args = {'business_id':M.curBusinessID, 'output':'excel'};
		if( this.orders.year != null ) { args.year = this.orders.year; }
		if( this.orders.month != null ) { args.month = this.orders.month; }
		if( this.orders.order_status != null ) { args.status = this.orders.status; }
//		window.open(M.api.getUploadURL('ciniki.sapos.invoiceList', args));
		M.api.openFile('ciniki.sapos.invoiceList', args);
	};
}
