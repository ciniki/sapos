function ciniki_sapos_customerinvoices() {
	this.init = function() {
		this.invoices = new M.panel('Customer Invoices',
			'ciniki_sapos_customerinvoices', 'invoices',
			'mc', 'large', 'sectioned', 'ciniki.sapos.customerinvoices.invoices');
		this.invoices.customer_id = 0;
		this.invoices.year = null;
		this.invoices.month = 0;
		this.invoices.status = 0;
		this.invoices.data = {};
		this.invoices.sections = {
//			'years':{'label':'', 'type':'paneltabs', 'selected':'', 'tabs':{}},
//			'months':{'label':'', 'visible':'no', 'type':'paneltabs', 'selected':'0', 'tabs':{
//				'0':{'label':'All', 'fn':'M.ciniki_sapos_invoices.showInvoices(null,null,0);'},
//				'1':{'label':'Jan', 'fn':'M.ciniki_sapos_invoices.showInvoices(null,null,1);'},
//				'2':{'label':'Feb', 'fn':'M.ciniki_sapos_invoices.showInvoices(null,null,2);'},
//				'3':{'label':'Mar', 'fn':'M.ciniki_sapos_invoices.showInvoices(null,null,3);'},
//				'4':{'label':'Apr', 'fn':'M.ciniki_sapos_invoices.showInvoices(null,null,4);'},
//				'5':{'label':'May', 'fn':'M.ciniki_sapos_invoices.showInvoices(null,null,5);'},
//				'6':{'label':'Jun', 'fn':'M.ciniki_sapos_invoices.showInvoices(null,null,6);'},
//				'7':{'label':'Jul', 'fn':'M.ciniki_sapos_invoices.showInvoices(null,null,7);'},
//				'8':{'label':'Aug', 'fn':'M.ciniki_sapos_invoices.showInvoices(null,null,8);'},
//				'9':{'label':'Sep', 'fn':'M.ciniki_sapos_invoices.showInvoices(null,null,9);'},
//				'10':{'label':'Oct', 'fn':'M.ciniki_sapos_invoices.showInvoices(null,null,10);'},
//				'11':{'label':'Nov', 'fn':'M.ciniki_sapos_invoices.showInvoices(null,null,11);'},
//				'12':{'label':'Dec', 'fn':'M.ciniki_sapos_invoices.showInvoices(null,null,12);'},
//				}},
			'statuses':{'label':'', 'visible':'yes', 'type':'paneltabs', 'selected':'0', 'tabs':{
				'0':{'label':'All', 'fn':'M.ciniki_sapos_customerinvoices.showInvoices(null,null,null,null,0);'},
				'20':{'label':'Payment Required', 'fn':'M.ciniki_sapos_customerinvoices.showInvoices(null,null,null,null,20);'},
				'40':{'label':'Deposit', 'fn':'M.ciniki_sapos_customerinvoices.showInvoices(null,null,null,null,40);'},
				'50':{'label':'Paid', 'fn':'M.ciniki_sapos_customerinvoices.showInvoices(null,null,null,null,50);'},
				'55':{'label':'Refunded', 'fn':'M.ciniki_sapos_customerinvoices.showInvoices(null,null,null,null,55);'},
				'60':{'label':'Void', 'fn':'M.ciniki_sapos_customerinvoices.showInvoices(null,null,null,null,60);'},
				}},
			'invoices':{'label':'', 'type':'simplegrid', 'num_cols':4,
				'sortable':'yes',
				'headerValues':['Invoice #', 'Date', 'Amount', 'Status'],
				'sortTypes':['number', 'date', 'number', 'text'],
				'noData':'No Invoices Found',
				},
//			'_buttons':{'label':'', 'buttons':{
//				'excel':{'label':'Download Excel', 'fn':'M.ciniki_sapos_invoices.downloadExcel();'},
//				}},
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
					case 2: return this.data.totals.total_amount;
					case 3: return '';
				}
			}
		};
		this.invoices.footerClass = function(s, i, d) {
			if( i == 3 ) { return 'alignright'; }
			return '';
		};
		this.invoices.sectionData = function(s) {
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
					case 2: return d.invoice.total_amount_display;
					case 3: return d.invoice.status_text;
				}
			}
		};
		
		this.invoices.rowFn = function(s, i, d) {
			if( s == 'invoices' ) {
				return 'M.startApp(\'ciniki.sapos.invoice\',null,\'M.ciniki_sapos_customerinvoices.showInvoices();\',\'mc\',{\'invoice_id\':\'' + d.invoice.id + '\'});';
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
		var appContainer = M.createContainer(appPrefix, 'ciniki_sapos_customerinvoices', 'yes');
		if( appContainer == null ) {
			alert('App Error');
			return false;
		} 
		this.showInvoices(cb, args.customer_id);
	};

	this.showInvoices = function(cb, cid, year, month, status) {
		if( cid != null ) { this.invoices.customer_id = cid; }
		if( status != null ) { 
			this.invoices.status = status; 
			this.invoices.sections.statuses.selected = status;
		}
		M.api.getJSONCb('ciniki.sapos.invoiceList', {'business_id':M.curBusinessID,
			'customer_id':this.invoices.customer_id, 'status':this.invoices.status}, function(rsp) {
				if( rsp.stat != 'ok' ) {
					M.api.err(rsp);
					return false;
				}
				var p = M.ciniki_sapos_customerinvoices.invoices;
				p.data = rsp;
//				p.sections._buttons.buttons.excel.visible=(rsp.invoices.length>0)?'yes':'no';
				p.refresh();
				p.show(cb);
			});
	};

//	this.downloadExcel = function() {
//		var args = {'business_id':M.curBusinessID, 'output':'excel'};
//		if( this.invoices.year != null ) { args.year = this.invoices.year; }
//		if( this.invoices.month != null ) { args.month = this.invoices.month; }
//		if( this.invoices.status != null ) { args.status = this.invoices.status; }
//		window.open(M.api.getUploadURL('ciniki.sapos.invoiceList', args));
//	};
}
