//
function ciniki_sapos_main() {
	this.init = function() {
		//
		// The menu panel
		//
		this.menu = new M.panel('Accounting',
			'ciniki_sapos_main', 'menu',
			'mc', 'medium', 'sectioned', 'ciniki.sapos.main.menu');
		this.menu.data = {};
		this.menu.sections = {
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
				'addFn':'M.startApp(\'ciniki.sapos.invoices\',null,\'M.ciniki_sapos_main.showMenu();\');',
				},
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
//			'_':{'label':'', 'list':{
//				'invoicesyearly':{'label':'Invoices', 'fn':'M.startApp(\'ciniki.sapos.invoices\',null,\'M.ciniki_sapos_main.showMenu();\',\'mc\',{\'monthly\':\'yes\'});'},
//				'invoicesmonthly':{'label':'Invoices', 'fn':'M.startApp(\'ciniki.sapos.invoices\',null,\'M.ciniki_sapos_main.showMenu();\',\'mc\',{\'monthly\':\'yes\'});'},
//				'expenses':{'label':'Expenses', 'fn':'M.startApp(\'ciniki.sapos.expenses\',null,\'M.ciniki_sapos_main.showMenu();\');'},
//				}},
//			'reports':{'label':'Reports', 'list':{
//				}},
			'_buttons':{'label':'', 'buttons':{
				'settings':{'label':'Setup Expenses', 'fn':'M.startApp(\'ciniki.sapos.settings\',null,\'M.ciniki_sapos_main.showMenu();\',\'mc\',{\'ecats\':\'yes\'});'},
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
			return '';
		};
		this.menu.liveSearchResultRowFn = function(s, f, i, j, d) {
			if( s == 'invoice_search' ) {
				return 'M.startApp(\'ciniki.sapos.invoice\',null,\'M.ciniki_sapos_main.showMenu();\',\'mc\',{\'invoice_id\':\'' + d.invoice.id + '\'});';
			}
			if( s == 'expense_search' ) {
				return 'M.startApp(\'ciniki.sapos.expense\',null,\'M.ciniki_sapos_main.showMenu();\',\'mc\',{\'expense_id\':\'' + d.expense.id + '\'});';
			}
		};
		this.menu.sectionData = function(s) {
			if( s == 'invoices' || s == 'expenses' ) { return this.data[s]; }
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
		};
		this.menu.rowFn = function(s, i, d) {
			if( s == 'invoices' ) {
				return 'M.startApp(\'ciniki.sapos.invoice\',null,\'M.ciniki_sapos_main.showMenu();\',\'mc\',{\'invoice_id\':\'' + d.invoice.id + '\'});';
			}
			if( s == 'expenses' ) {
				return 'M.startApp(\'ciniki.sapos.expense\',null,\'M.ciniki_sapos_main.showMenu();\',\'mc\',{\'expense_id\':\'' + d.expense.id + '\'});';
			}
		};
		this.menu.addButton('add_i', 'Invoice', 'M.startApp(\'ciniki.sapos.invoice\',null,\'M.ciniki_sapos_main.showMenu();\',\'mc\',{});', 'add');
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

		this.showMenu(cb);
	};

	//
	// Grab the stats for the business from the database and present the list of orders.
	//
	this.showMenu = function(cb) {
		//
		// Get recent invoices
		//
		M.api.getJSONCb('ciniki.sapos.latest', {'business_id':M.curBusinessID,
			'limit':5, 'sort':'latest'}, function(rsp) {
				if( rsp.stat != 'ok' ) {
					M.api.err(rsp);
					return false;
				}
				var p = M.ciniki_sapos_main.menu;
				p.data.invoices = rsp.invoices;
				p.data.expenses = rsp.expenses;
				p.sections._quickadd.visible = ((M.curBusiness.modules['ciniki.sapos'].flags&0x04)>0)?'yes':'no';
				p.sections.invoice_search.visible = (rsp.invoices.length > 0)?'yes':'no';
				p.sections.invoices.visible = (rsp.invoices.length > 0)?'yes':'no';
				p.sections.expense_search.visible = (rsp.expenses.length > 0)?'yes':'no';
				p.sections.expenses.visible = (rsp.expenses.length > 0)?'yes':'no';
				if( rsp.numcats != null && rsp.numcats == 0 && (M.curBusiness.modules['ciniki.sapos'].flags&0x04)>0 ) {
					if( p.rightbuttons['add_e'] != null ) {
						delete p.rightbuttons['add_e'];
					}
					p.sections._buttons.visible = 'yes';
					p.sections._buttons.buttons.settings.visible = 'yes';
				} else {
					p.addButton('add_e', 'Expense', 'M.startApp(\'ciniki.sapos.expense\',null,\'M.ciniki_sapos_main.showMenu();\');', 'add');
					p.sections._buttons.visible = 'no';
					p.sections._buttons.buttons.settings.visible = 'no';
				}
				p.refresh();
				p.show(cb);
			});
	};
}
