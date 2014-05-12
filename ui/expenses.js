//
// This panel will display the list of expenses in a grid similar to a spreadsheet
//
function ciniki_sapos_expenses() {
	this.init = function() {
		this.expenses = new M.panel('Expenses',
			'ciniki_sapos_expenses', 'expenses',
			'mc', 'full', 'sectioned', 'ciniki.sapos.expenses.expenses');
		this.expenses.year = null;
		this.expenses.month = 0;
		this.expenses.categories = {};
		this.expenses.data = {};
		this.expenses.sections = {
			'years':{'label':'', 'type':'paneltabs', 'selected':'', 'tabs':{}},
			'months':{'label':'', 'visible':'no', 'type':'paneltabs', 'selected':'0', 'tabs':{
				'0':{'label':'All', 'fn':'M.ciniki_sapos_expenses.showExpenses(null,null,0);'},
				'1':{'label':'Jan', 'fn':'M.ciniki_sapos_expenses.showExpenses(null,null,1);'},
				'2':{'label':'Feb', 'fn':'M.ciniki_sapos_expenses.showExpenses(null,null,2);'},
				'3':{'label':'Mar', 'fn':'M.ciniki_sapos_expenses.showExpenses(null,null,3);'},
				'4':{'label':'Apr', 'fn':'M.ciniki_sapos_expenses.showExpenses(null,null,4);'},
				'5':{'label':'May', 'fn':'M.ciniki_sapos_expenses.showExpenses(null,null,5);'},
				'6':{'label':'Jun', 'fn':'M.ciniki_sapos_expenses.showExpenses(null,null,6);'},
				'7':{'label':'Jul', 'fn':'M.ciniki_sapos_expenses.showExpenses(null,null,7);'},
				'8':{'label':'Aug', 'fn':'M.ciniki_sapos_expenses.showExpenses(null,null,8);'},
				'9':{'label':'Sep', 'fn':'M.ciniki_sapos_expenses.showExpenses(null,null,9);'},
				'10':{'label':'Oct', 'fn':'M.ciniki_sapos_expenses.showExpenses(null,null,10);'},
				'11':{'label':'Nov', 'fn':'M.ciniki_sapos_expenses.showExpenses(null,null,11);'},
				'12':{'label':'Dec', 'fn':'M.ciniki_sapos_expenses.showExpenses(null,null,12);'},
				}},
			'expenses':{'label':'', 'type':'simplegrid', 'num_cols':5,
				'sortable':'yes',
				'sortTypes':['date', 'text', 'number', 'number', 'number'],
				'noData':'No Expenses Found',
				},
			'_buttons':{'label':'', 'buttons':{
				'excel':{'label':'Download Excel', 'fn':'M.ciniki_sapos_expenses.downloadExcel();'},
				}},
		};
		this.expenses.sectionData = function(s) {
			return this.data[s];
		};
		this.expenses.headerValue = function(s, i, d) {
			if( i == 0 ) { return 'Date'; }
			if( i == 1 ) { return 'Name'; }
			if( i < this.sections[s].num_cols-1 ) {
				return this.categories[i-2].category.name;
			} else {
				return 'Total';
			}
		};
		this.expenses.footerValue = function(s, i, d) {
			if( i < 2 ) { return ''; }
			if( i < this.sections[s].num_cols-1 ) {
				return this.categories[i-2].category.total_amount_display;
			} else {
				return this.data.totals.total_amount_display;
			}
		};
		this.expenses.footerClass = function(s, i, d) {
			if( i > 1 ) { return 'alignright'; }
		};
		this.expenses.noData = function(s) {
			return this.sections[s].noData;
		};
		this.expenses.cellValue = function(s, i, j, d) {
			if( j == 0 ) { return d.expense.invoice_date; }
			if( j == 1 ) { return d.expense.name; }
			if( j < this.sections[s].num_cols-1 ) {
				for(k in d.expense.items) {
					if( d.expense.items[k].item.category_id == this.categories[j-2].category.id ) {
						return d.expense.items[k].item.amount_display;
					}
				}
				return '';
			} else {
				return d.expense.total_amount_display;
			}
		};
		this.expenses.cellClass = function(s, i, j, d) {
			if( j > 1 ) { return 'alignright'; }
		};
		this.expenses.rowFn = function(s, i, d) {
			if( s == 'expenses' ) {
				return 'M.startApp(\'ciniki.sapos.expense\',null,\'M.ciniki_sapos_expenses.showExpenses();\',\'mc\',{\'expense_id\':\'' + d.expense.id + '\'});';
			}
		};
		this.expenses.addButton('add', 'Expense', 'M.startApp(\'ciniki.sapos.expense\',null,\'M.ciniki_sapos_expenses.showExpenses();\',\'mc\',{});');
		this.expenses.addClose('Back');
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
		var appContainer = M.createContainer(appPrefix, 'ciniki_sapos_expenses', 'yes');
		if( appContainer == null ) {
			alert('App Error');
			return false;
		} 

		this.expenses.categories = {};
		M.api.getJSONCb('ciniki.sapos.expenseStats', {'business_id':M.curBusinessID}, function(rsp) {
			if( rsp.stat != 'ok' ) {
				M.api.err(rsp);
				return false;
			}
			var p = M.ciniki_sapos_expenses.expenses;
			if( rsp.stats.min_invoice_date_year != null ) {
				var year = new Date().getFullYear();
				p.sections.years.tabs = {};
				for(var i=rsp.stats.min_invoice_date_year;i<=year;i++) {
					p.sections.years.tabs[i] = {'label':i, 'fn':'M.ciniki_sapos_expenses.showExpenses(null,' + i + ',null);'};
				}
			}
			var dt = new Date();
//			p.categories = rsp.categories;
//			p.sections.expenses.num_cols = rsp.categories.length + 3;
			M.ciniki_sapos_expenses.showExpenses(cb, dt.getFullYear(), 0);
		});
	};

	this.showExpenses = function(cb, year, month) {
		if( year != null ) {
			this.expenses.year = year;
			this.expenses.sections.years.selected = year;
		}
		if( month != null ) {
			this.expenses.month = month;
			this.expenses.sections.months.selected = month;
		}
		this.expenses.sections.months.visible = (this.expenses.month>0)?'yes':'yes';
		M.api.getJSONCb('ciniki.sapos.expenseGrid', {'business_id':M.curBusinessID,
			'year':this.expenses.year, 'month':this.expenses.month}, function(rsp) {
				if( rsp.stat != 'ok' ) {
					M.api.err(rsp);
					return false;
				}
				var p = M.ciniki_sapos_expenses.expenses;
				p.data.expenses = rsp.expenses;
				p.categories = rsp.categories;
				p.sections.expenses.num_cols = rsp.categories.length + 3;
				p.sections.expenses.sortTypes = ['date', 'text'];
				for(i=0;i<rsp.categories.length;i++) {
					p.sections.expenses.sortTypes.push('number');
				}
				p.sections.expenses.sortTypes.push('number');
				p.data.totals = rsp.totals;
//				p.sections.expenses.visible = (rsp.expenses.length > 0)?'yes':'no';
				p.refresh();
				p.show(cb);
			});
	};

	this.downloadExcel = function() {
		var args = {'business_id':M.curBusinessID, 'output':'excel'};
		if( this.expenses.year != null ) { args.year = this.expenses.year; }
		if( this.expenses.month != null ) { args.month = this.expenses.month; }
		window.open(M.api.getUploadURL('ciniki.sapos.expenseGrid', args));
	};
}
