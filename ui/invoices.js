//
function ciniki_sapos_invoices() {
	this.init = function() {
		this.invoices = new M.panel('Invoices',
			'ciniki_sapos_invoices', 'invoices',
			'mc', 'large', 'sectioned', 'ciniki.sapos.invoices.invoices');
		this.invoices.year = null;
		this.invoices.month = 0;
		this.invoices.status = 0;
		this.invoices.data = {};
		this.invoices.sections = {
			'years':{'label':'', 'type':'paneltabs', 'selected':'', 'tabs':{}},
			'months':{'label':'', 'visible':'no', 'type':'paneltabs', 'selected':'0', 'tabs':{
				'0':{'label':'All', 'fn':'M.ciniki_sapos_invoices.showInvoices(null,null,0);'},
				'1':{'label':'Jan', 'fn':'M.ciniki_sapos_invoices.showInvoices(null,null,1);'},
				'2':{'label':'Feb', 'fn':'M.ciniki_sapos_invoices.showInvoices(null,null,2);'},
				'3':{'label':'Mar', 'fn':'M.ciniki_sapos_invoices.showInvoices(null,null,3);'},
				'4':{'label':'Apr', 'fn':'M.ciniki_sapos_invoices.showInvoices(null,null,4);'},
				'5':{'label':'May', 'fn':'M.ciniki_sapos_invoices.showInvoices(null,null,5);'},
				'6':{'label':'Jun', 'fn':'M.ciniki_sapos_invoices.showInvoices(null,null,6);'},
				'7':{'label':'Jul', 'fn':'M.ciniki_sapos_invoices.showInvoices(null,null,7);'},
				'8':{'label':'Aug', 'fn':'M.ciniki_sapos_invoices.showInvoices(null,null,8);'},
				'9':{'label':'Sep', 'fn':'M.ciniki_sapos_invoices.showInvoices(null,null,9);'},
				'10':{'label':'Oct', 'fn':'M.ciniki_sapos_invoices.showInvoices(null,null,10);'},
				'11':{'label':'Nov', 'fn':'M.ciniki_sapos_invoices.showInvoices(null,null,11);'},
				'12':{'label':'Dec', 'fn':'M.ciniki_sapos_invoices.showInvoices(null,null,12);'},
				}},
			'statuses':{'label':'', 'visible':'yes', 'type':'paneltabs', 'selected':'0', 'tabs':{
				'0':{'label':'All', 'fn':'M.ciniki_sapos_invoices.showInvoices(null,null,null,0);'},
				'20':{'label':'Payment Required', 'fn':'M.ciniki_sapos_invoices.showInvoices(null,null,null,20);'},
				'40':{'label':'Deposit', 'fn':'M.ciniki_sapos_invoices.showInvoices(null,null,null,40);'},
				'50':{'label':'Paid', 'fn':'M.ciniki_sapos_invoices.showInvoices(null,null,null,50);'},
				'55':{'label':'Refunded', 'fn':'M.ciniki_sapos_invoices.showInvoices(null,null,null,55);'},
				'60':{'label':'Void', 'fn':'M.ciniki_sapos_invoices.showInvoices(null,null,null,60);'},
				}},
			'invoices':{'label':'', 'type':'simplegrid', 'num_cols':5,
				'sortable':'yes',
				'headerValues':['Invoice #', 'Date', 'Customer', 'Amount', 'Status'],
				'sortTypes':['number', 'date', 'text', 'number', 'text'],
				'noData':'No Invoices Found',
				},
//			'totals':{'label':'Totals', 'list':{
//				'num_invoices':{'label':'Number of Invoices'},
//				'total_amount':{'label':'Amount Invoiced'},
//				}},
		};
		this.invoices.footerValue = function(s, i, d) {
			if( this.data.totals != null ) {
				switch(i) {
					case 0: return this.data.totals.num_invoices;
					case 1: return '';
					case 2: return '';
					case 3: return this.data.totals.total_amount;
					case 4: return '';
				}
			}
		};
		this.invoices.footerClass = function(s, i, d) {
			if( i == 4 ) { return 'alignright'; }
			return '';
		};
		this.invoices.sectionData = function(s) {
//			if( s == 'totals' ) { return this.sections[s].list; }
			return this.data[s];
		};
		this.invoices.noData = function(s) {
			return this.sections[s].noData;
		};
		this.invoices.listLabel = function(s, i, d) {
			return d.label;
		};
		this.invoices.listValue = function(s, i, d) {
			return this.data.totals[i];
		};
		this.invoices.cellValue = function(s, i, j, d) {
			if( s == 'invoices' ) {
				switch(j) {
					case 0: return d.invoice.invoice_number;
					case 1: return d.invoice.invoice_date;
					case 2: return d.invoice.customer_name;
					case 3: return d.invoice.total_amount_display;
					case 4: return d.invoice.status_text;
				}
			}
		};
		
		this.invoices.rowFn = function(s, i, d) {
			if( s == 'invoices' ) {
				return 'M.startApp(\'ciniki.sapos.invoice\',null,\'M.ciniki_sapos_invoices.showInvoices();\',\'mc\',{\'invoice_id\':\'' + d.invoice.id + '\'});';
			}
		};
		this.invoices.addClose('Back');
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
		var appContainer = M.createContainer(appPrefix, 'ciniki_sapos_invoices', 'yes');
		if( appContainer == null ) {
			alert('App Error');
			return false;
		} 

		M.api.getJSONCb('ciniki.sapos.invoiceStats', {'business_id':M.curBusinessID}, function(rsp) {
			if( rsp.stat != 'ok' ) {
				M.api.err(rsp);
				return false;
			}
			var p = M.ciniki_sapos_invoices.invoices;
			if( rsp.stats.min_invoice_date_year != null ) {
				var year = new Date().getFullYear();
				p.sections.years.tabs = {};
				for(var i=rsp.stats.min_invoice_date_year;i<=year;i++) {
					p.sections.years.tabs[i] = {'label':i, 'fn':'M.ciniki_sapos_invoices.showInvoices(null,' + i + ',null);'};
				}
			}
			var dt = new Date();
			M.ciniki_sapos_invoices.showInvoices(cb, dt.getFullYear(), 0, 0);
		});
	};

	this.showInvoices = function(cb, year, month, status) {
		if( year != null ) {
			this.invoices.year = year;
			this.invoices.sections.years.selected = year;
		}
		if( month != null ) {
			this.invoices.month = month;
			this.invoices.sections.months.selected = month;
		}
		if( status != null ) {
			this.invoices.status = status;
			this.invoices.sections.statuses.selected = status;
		}
		this.invoices.sections.months.visible = (this.invoices.month>0)?'yes':'yes';
		M.api.getJSONCb('ciniki.sapos.invoiceList', {'business_id':M.curBusinessID,
			'year':this.invoices.year, 'month':this.invoices.month, 
			'status':this.invoices.status}, function(rsp) {
				if( rsp.stat != 'ok' ) {
					M.api.err(rsp);
					return false;
				}
				var p = M.ciniki_sapos_invoices.invoices;
				p.data.invoices = rsp.invoices;
				p.data.totals = rsp.totals;
//				p.sections.invoices.visible = (rsp.invoices.length > 0)?'yes':'no';
				p.refresh();
				p.show(cb);
			});
	};
}
