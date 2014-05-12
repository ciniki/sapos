//
function ciniki_sapos_main() {
	this.init = function() {
		//
		// The menu panel
		//
		this.menu = new M.panel('Accounting',
			'ciniki_sapos_main', 'menu',
			'mc', 'medium', 'sectioned', 'ciniki.sapos.main.menu');
		this.menu.data = {'invoice_type':'invoices'};
		this.menu.invoice_type = 10;
		this.menu.formtab = 'invoices';
		this.menu.formtabs = {'label':'', 'visible':'no', 'tabs':{
			'invoices':{'label':'Invoices', 'visible':'no', 'fn':'M.ciniki_sapos_main.showMenu(null,"invoices");'},
			'carts':{'label':'Carts', 'visible':'no', 'fn':'M.ciniki_sapos_main.showMenu(null,"carts");'},
			'pos':{'label':'POS', 'visible':'no', 'fn':'M.ciniki_sapos_main.showMenu(null,"pos");'},
			'orders':{'label':'Orders', 'visible':'no', 'fn':'M.ciniki_sapos_main.showMenu(null,"orders");'},
			'expenses':{'label':'Expenses', 'visible':'no', 'fn':'M.ciniki_sapos_main.showMenu(null,"expenses");'},
			'mileage':{'label':'Mileage', 'visible':'no', 'fn':'M.ciniki_sapos_main.showMenu(null,"mileage");'},
			}};
		this.menu.forms = {};
		this.menu.forms.invoices = {
			'_quickadd':{'label':'', 'visible':'no', 'buttons':{
				'quickadd':{'label':'Quick Invoice', 'fn':'M.startApp(\'ciniki.sapos.qi\',null,\'M.ciniki_sapos_main.showMenu();\');'},
				}},
			'invoice_search':{'label':'Invoices', 'type':'livesearchgrid', 'livesearchcols':5, 
				'headerValues':['Invoice #','Date','Customer','Amount','Status'],
				'hint':'Search invoice # or customer name', 
				'noData':'No Invoices Found',
				},
			'invoices':{'label':'Recent Invoices', 'type':'simplegrid', 'num_cols':5,
				'headerValues':['Invoice #','Date','Customer','Amount','Status'],
				'noData':'No Invoices',
				'addTxt':'More',
				'addFn':'M.startApp(\'ciniki.sapos.invoices\',null,\'M.ciniki_sapos_main.showMenu();\',\'mc\',{\'invoice_type\':\'10\'});',
				},
			};
		this.menu.forms.carts = {
			'invoice_search':{'label':'Shopping Carts', 'type':'livesearchgrid', 'livesearchcols':5, 
				'headerValues':['Invoice #','Date','Customer','Amount','Status'],
				'hint':'Search invoice # or customer name', 
				'noData':'No Invoices Found',
				},
			'invoices':{'label':'Recent Carts', 'type':'simplegrid', 'num_cols':5,
				'headerValues':['Invoice #','Date','Customer','Amount','Status'],
				'noData':'No Invoices',
				'addTxt':'More',
				'addFn':'M.startApp(\'ciniki.sapos.invoices\',null,\'M.ciniki_sapos_main.showMenu();\',\'mc\',{\'invoice_type\':\'20\'});',
				},
			};
		this.menu.forms.pos = {
			'invoice_search':{'label':'Post of Sale', 'type':'livesearchgrid', 'livesearchcols':5, 
				'headerValues':['Invoice #','Date','Customer','Amount','Status'],
				'hint':'Search invoice # or customer name', 
				'noData':'No Invoices Found',
				},
			'invoices':{'label':'Recent Sales', 'type':'simplegrid', 'num_cols':5,
				'headerValues':['Invoice #','Date','Customer','Amount','Status'],
				'noData':'No Invoices',
				'addTxt':'More',
				'addFn':'M.startApp(\'ciniki.sapos.invoices\',null,\'M.ciniki_sapos_main.showMenu();\',\'mc\',{\'invoice_type\':\'30\'});',
				},
			};
		this.menu.forms.orders = {
			'invoice_search':{'label':'Purchase Orders', 'type':'livesearchgrid', 'livesearchcols':5, 
				'headerValues':['Invoice #','Date','Customer','Amount','Status'],
				'hint':'Search invoice # or customer name', 
				'noData':'No Invoices Found',
				},
			'invoices':{'label':'Recent Orders', 'type':'simplegrid', 'num_cols':5,
				'headerValues':['Invoice #','Date','Customer','Amount','Status'],
				'noData':'No Invoices',
				'addTxt':'More',
				'addFn':'M.startApp(\'ciniki.sapos.invoices\',null,\'M.ciniki_sapos_main.showMenu();\',\'mc\',{\'invoice_type\':\'40\'});',
				},
			};
		this.menu.forms.expenses = {
			'expense_search':{'label':'Expenses', 'type':'livesearchgrid', 'livesearchcols':3, 
				'headerValues':['Expense','Date','Amount'],
				'hint':'Search expenses', 
				'noData':'No Expenses Found',
				},
			'expenses':{'label':'Recent Expenses', 'type':'simplegrid', 'num_cols':3,
				'headerValues':['Expense', 'Date','Amount'],
				'noData':'No Expenses',
				'addTxt':'More',
				'addFn':'M.startApp(\'ciniki.sapos.expenses\',null,\'M.ciniki_sapos_main.showMenu();\');',
				},
			'_buttons':{'label':'', 'visible':'no', 'buttons':{
				'settings':{'label':'Setup Expenses', 'visible':'no', 'fn':'M.startApp(\'ciniki.sapos.settings\',null,\'M.ciniki_sapos_main.showMenu();\',\'mc\',{\'ecats\':\'yes\'});'},
				}},
			};
		this.menu.forms.mileage = {
			'mileage_search':{'label':'Mileage', 'type':'livesearchgrid', 'livesearchcols':3, 
				'headerValues':['Date', 'From/To', 'Distance'],
				'hint':'Search mileage', 
				'noData':'No Mileage Entries Found',
				},
			'mileages':{'label':'Recent Mileage', 'type':'simplegrid', 'num_cols':4,
				'headerValues':['Date', 'From/To', 'Distance', 'Amount'],
				'noData':'No Mileage',
				'addTxt':'More',
				'addFn':'M.startApp(\'ciniki.sapos.mileages\',null,\'M.ciniki_sapos_main.showMenu();\');',
				},
			'_buttons':{'label':'', 'visible':'no', 'buttons':{
				'settings':{'label':'Setup Mileage', 'visible':'no', 'fn':'M.startApp(\'ciniki.sapos.settings\',null,\'M.ciniki_sapos_main.showMenu();\',\'mc\',{\'mrates\':\'yes\'});'},
				}},
			};
		this.menu.liveSearchCb = function(s, i, v) {
			if( s == 'invoice_search' && v != '' ) {
				M.api.getJSONBgCb('ciniki.sapos.invoiceSearch', {'business_id':M.curBusinessID,
					'start_needle':v, 'sort':'reverse', 'limit':'10'}, function(rsp) {
						M.ciniki_sapos_main.menu.liveSearchShow('invoice_search',null,M.gE(M.ciniki_sapos_main.menu.panelUID + '_' + s), rsp.invoices);
					});
			}
			else if( s == 'expense_search' && v != '' ) {
				M.api.getJSONBgCb('ciniki.sapos.expenseSearch', {'business_id':M.curBusinessID,
					'start_needle':v, 'sort':'reverse', 'limit':'10'}, function(rsp) {
						M.ciniki_sapos_main.menu.liveSearchShow('expense_search',null,M.gE(M.ciniki_sapos_main.menu.panelUID + '_' + s), rsp.expenses);
					});
			}
			else if( s == 'mileage_search' && v != '' ) {
				M.api.getJSONBgCb('ciniki.sapos.mileageSearch', {'business_id':M.curBusinessID,
					'start_needle':v, 'sort':'reverse', 'limit':'10'}, function(rsp) {
						M.ciniki_sapos_main.menu.liveSearchShow('mileage_search',null,M.gE(M.ciniki_sapos_main.menu.panelUID + '_' + s), rsp.mileages);
					});
			}
		};
		this.menu.liveSearchResultValue = function(s, f, i, j, d) {
			if( s == 'invoice_search' ) { 
				switch (j) {
					case 0: return d.invoice.invoice_number;
					case 1: return d.invoice.invoice_date;
					case 2: return d.invoice.customer_display_name;
					case 3: return d.invoice.total_amount_display;
					case 4: return d.invoice.status_text;
				}
			}
			else if( s == 'expense_search' ) { 
				switch (j) {
					case 0: return d.expense.name;
					case 1: return d.expense.invoice_date;
					case 2: return d.expense.total_amount_display;
				}
			}
			else if( s == 'mileage_search' ) { 
				switch (j) {
					case 0: return d.mileage.travel_date;
					case 1: return d.mileage.start_name + ' - ' + d.mileage.end_name;
					case 2: return d.mileage.total_distance + ' ' + d.mileage.units;
					case 3: return d.mileage.amount_display;
				}
			}
			return '';
		};
		this.menu.liveSearchResultRowFn = function(s, f, i, j, d) {
			if( s == 'invoice_search' ) {
				return 'M.startApp(\'ciniki.sapos.invoice\',null,\'M.ciniki_sapos_main.showMenu();\',\'mc\',{\'invoice_id\':\'' + d.invoice.id + '\'});';
			}
			if( s == 'expense_search' ) {
				return 'M.startApp(\'ciniki.sapos.expense\',null,\'M.ciniki_sapos_main.showMenu();\',\'mc\',{\'expense_id\':\'' + d.expense.id + '\'});';
			}
			if( s == 'mileage_search' ) {
				return 'M.startApp(\'ciniki.sapos.mileage\',null,\'M.ciniki_sapos_main.showMenu();\',\'mc\',{\'mileage_id\':\'' + d.mileage.id + '\'});';
			}
		};
		this.menu.sectionData = function(s) {
			if( s == 'invoices' || s == 'expenses' || s == 'mileages' ) { return this.data[s]; }
			return this.sections[s].list;
		};
		this.menu.noData = function(s) {
			return this.sections[s].noData;
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
			if( s == 'expenses' ) {
				switch(j) {
					case 0: return d.expense.name;
					case 1: return d.expense.invoice_date;
					case 2: return d.expense.total_amount_display;
				}
			}
			if( s == 'mileages' ) {
				switch(j) {
					case 0: return d.mileage.travel_date;
					case 1: return d.mileage.start_name + ' - ' + d.mileage.end_name;
					case 2: return d.mileage.total_distance + ' ' + d.mileage.units;
					case 3: return d.mileage.amount_display;
				}
			}
		};
		this.menu.rowFn = function(s, i, d) {
			if( s == 'invoices' ) {
				return 'M.startApp(\'ciniki.sapos.invoice\',null,\'M.ciniki_sapos_main.showMenu();\',\'mc\',{\'invoice_id\':\'' + d.invoice.id + '\'});';
			}
			if( s == 'expenses' ) {
				return 'M.startApp(\'ciniki.sapos.expense\',null,\'M.ciniki_sapos_main.showMenu();\',\'mc\',{\'expense_id\':\'' + d.expense.id + '\'});';
			}
			if( s == 'mileages' ) {
				return 'M.startApp(\'ciniki.sapos.mileage\',null,\'M.ciniki_sapos_main.showMenu();\',\'mc\',{\'mileage_id\':\'' + d.mileage.id + '\'});';
			}
		};
//		this.menu.addButton('add_i', 'Invoice', 'M.startApp(\'ciniki.sapos.invoice\',null,\'M.ciniki_sapos_main.showMenu();\',\'mc\',{});', 'add');
//		this.menu.addButton('add_e', 'Expense', 'M.startApp(\'ciniki.sapos.expense\',null,\'M.ciniki_sapos_main.showMenu();\',\'mc\',{});', 'add');
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
		var appContainer = M.createContainer(appPrefix, 'ciniki_sapos_main', 'yes');
		if( appContainer == null ) {
			alert('App Error');
			return false;
		} 

		this.menu.forms.invoices._quickadd.visible = ((M.curBusiness.modules['ciniki.sapos'].flags&0x04)>0)?'yes':'no';
		
		// Remove add buttons
		if( this.menu.rightbuttons['add_i'] != null ) { delete this.menu.rightbuttons['add_i']; }
		if( this.menu.rightbuttons['add_e'] != null ) { delete this.menu.rightbuttons['add_e']; }
		if( this.menu.rightbuttons['add_m'] != null ) { delete this.menu.rightbuttons['add_m']; }
		if( this.menu.leftbuttons['add_m'] != null ) { delete this.menu.leftbuttons['add_m']; }
		// Setup the panel tabs
		var ct = 0;
		var sp = '';
		if( (M.curBusiness.modules['ciniki.sapos'].flags&0x01) > 0 ) {
			this.menu.formtabs.tabs.invoices.visible = 'yes';
			if( sp == '' ) { sp = 'invoices'; }
			ct++;
		} else {
			this.menu.formtabs.tabs.invoices.visible = 'no';
		}
		if( (M.curBusiness.modules['ciniki.sapos'].flags&0x08) > 0 ) {
			this.menu.formtabs.tabs.carts.visible = 'yes';
			if( sp == '' ) { sp = 'carts'; }
			ct++;
		} else {
			this.menu.formtabs.tabs.carts.visible = 'no';
		}
		if( (M.curBusiness.modules['ciniki.sapos'].flags&0x10) > 0 ) {
			this.menu.formtabs.tabs.pos.visible = 'yes';
			if( sp == '' ) { sp = 'pos'; }
			ct++;
		} else {
			this.menu.formtabs.tabs.pos.visible = 'no';
		}
		if( (M.curBusiness.modules['ciniki.sapos'].flags&0x20) > 0 ) {
			this.menu.formtabs.tabs.orders.visible = 'yes';
			if( sp == '' ) { sp = 'orders'; }
			ct++;
		} else {
			this.menu.formtabs.tabs.orders.visible = 'no';
		}
		var bts = 0;
		if( ct > 0 ) {
			this.menu.addButton('add_i', 'Invoice', 'M.startApp(\'ciniki.sapos.invoice\',null,\'M.ciniki_sapos_main.showMenu();\',\'mc\',{});', 'add');
			bts++;
		}
		if( (M.curBusiness.modules['ciniki.sapos'].flags&0x02) > 0 ) {
			this.menu.formtabs.tabs.expenses.visible = 'yes';
			this.menu.addButton('add_e', 'Expense', 'M.startApp(\'ciniki.sapos.expense\',null,\'M.ciniki_sapos_main.showMenu();\',\'mc\',{});', 'add');
			bts++;
			if( sp == '' ) { sp = 'expenses'; }
			ct++;
		} else {
			this.menu.formtabs.tabs.expenses.visible = 'no';
		}
		if( (M.curBusiness.modules['ciniki.sapos'].flags&0x100) > 0 ) {
			this.menu.formtabs.tabs.mileage.visible = 'yes';
			if( bts >= 2 ) {
				this.menu.addLeftButton('add_m', 'Mileage', 'M.startApp(\'ciniki.sapos.mileage\',null,\'M.ciniki_sapos_main.showMenu();\',\'mc\',{});', 'add');
			} else {
				this.menu.addButton('add_m', 'Mileage', 'M.startApp(\'ciniki.sapos.mileage\',null,\'M.ciniki_sapos_main.showMenu();\',\'mc\',{});', 'add');
			}
			if( sp == '' ) { sp = 'mileage'; }
			ct++;
		} else {
			this.menu.formtabs.tabs.mileage.visible = 'no';
		}
		if( ct > 1 ) {
			this.menu.formtabs.visible = 'yes';
		} else {
			this.menu.formtabs.visible = 'no';
		}

		this.showMenu(cb, sp);
	};

	//
	// Grab the stats for the business from the database and present the list of orders.
	//
	this.showMenu = function(cb, type) {
		if( type != null ) { this.menu.formtab = type; }
		
		if( this.menu.formtab == 'invoices' 
			|| this.menu.formtab == 'carts'
			|| this.menu.formtab == 'pos'
			|| this.menu.formtab == 'orders'
			) {
			switch(this.menu.formtab) {
				case 'invoices': this.menu.invoice_type = 10; break;
				case 'carts': this.menu.invoice_type = 20; break;
				case 'pos': this.menu.invoice_type = 30; break;
				case 'orders': this.menu.invoice_type = 40; break;
			}
			M.api.getJSONCb('ciniki.sapos.latest', {'business_id':M.curBusinessID,
				'limit':'10', 'sort':'latest', 'type':this.menu.invoice_type}, function(rsp) {
					if( rsp.stat != 'ok' ) {
						M.api.err(rsp);
						return false;
					}
					var p = M.ciniki_sapos_main.menu;
					p.data.invoices = rsp.invoices;
					p.forms.invoices.invoice_search.visible = (rsp.invoices.length > 0)?'yes':'no';
					p.forms.invoices.invoices.visible = (rsp.invoices.length > 0)?'yes':'no';
					p.refresh();
					p.show(cb);
				});
		} 

		else if( this.menu.formtab == 'expenses' ) {
			M.api.getJSONCb('ciniki.sapos.latest', {'business_id':M.curBusinessID,
				'limit':'10', 'sort':'latest', 'type':'expenses'}, function(rsp) {
					if( rsp.stat != 'ok' ) {
						M.api.err(rsp);
						return false;
					}
					var p = M.ciniki_sapos_main.menu;
					p.data.expenses = rsp.expenses;
					if( rsp.expenses.length > 0 ) {
						p.forms.expenses.expense_search.visible = 'yes';
						p.forms.expenses.expenses.visible = 'yes';
						p.forms.expenses._buttons.visible = 'no';
						p.forms.expenses._buttons.buttons.settings.visible = 'no';
					} else {
						p.forms.expenses.expense_search.visible = 'no';
						p.forms.expenses.expenses.visible = 'no';
						p.forms.expenses._buttons.visible = 'yes';
						p.forms.expenses._buttons.buttons.settings.visible = 'yes';
					}
					p.refresh();
					p.show(cb);
				});
		}
		else if( this.menu.formtab == 'mileage' ) {
			M.api.getJSONCb('ciniki.sapos.latest', {'business_id':M.curBusinessID,
				'limit':'10', 'sort':'latest', 'type':'mileage'}, function(rsp) {
					if( rsp.stat != 'ok' ) {
						M.api.err(rsp);
						return false;
					}
					var p = M.ciniki_sapos_main.menu;
					p.data.mileages = rsp.mileages;
					if( rsp.mileages.length > 0 ) {
						p.forms.mileage.mileage_search.visible = 'yes';
						p.forms.mileage.mileages.visible = 'yes';
						p.forms.mileage._buttons.visible = 'no';
						p.forms.mileage._buttons.buttons.settings.visible = 'no';
					} else {
						p.forms.mileage.mileage_search.visible = 'no';
						p.forms.mileage.mileages.visible = 'no';
						p.forms.mileage._buttons.visible = 'yes';
						p.forms.mileage._buttons.buttons.settings.visible = 'yes';
					}
					p.refresh();
					p.show(cb);
				});
		}


		//
		// Get recent invoices
		//
//		M.api.getJSONCb('ciniki.sapos.latest', {'business_id':M.curBusinessID,
//			'limit':5, 'sort':'latest'}, function(rsp) {
//				if( rsp.stat != 'ok' ) {
//					M.api.err(rsp);
//					return false;
//				}
//				var p = M.ciniki_sapos_main.menu;
//				p.data.invoices = rsp.invoices;
//				p.data.expenses = rsp.expenses;
//				p.forms.invoices._quickadd.visible = ((M.curBusiness.modules['ciniki.sapos'].flags&0x04)>0)?'yes':'no';
//				p.forms.invoices.invoice_search.visible = (rsp.invoices.length > 0)?'yes':'no';
//				p.forms.invoices.invoices.visible = (rsp.invoices.length > 0)?'yes':'no';
//				p.forms.expenses.expense_search.visible = (rsp.expenses.length > 0)?'yes':'no';
//				p.forms.expenses.expenses.visible = (rsp.expenses.length > 0)?'yes':'no';
//				if( rsp.numcats != null && rsp.numcats == 0 && (M.curBusiness.modules['ciniki.sapos'].flags&0x02)>0 ) {
//					if( p.rightbuttons['add_e'] != null ) {
//						delete p.rightbuttons['add_e'];
//					}
//					p.sections._buttons.visible = 'yes';
//					p.sections._buttons.buttons.settings.visible = 'yes';
//				} else {
//					p.addButton('add_e', 'Expense', 'M.startApp(\'ciniki.sapos.expense\',null,\'M.ciniki_sapos_main.showMenu();\');', 'add');
//					p.sections._buttons.visible = 'no';
//					p.sections._buttons.buttons.settings.visible = 'no';
//				}
//				p.refresh();
//				p.show(cb);
//			});
	};
}
