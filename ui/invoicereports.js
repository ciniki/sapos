function ciniki_sapos_invoicereports() {
	this.init = function() {
		this.taxreport = new M.panel('Tax Report',
			'ciniki_sapos_invoicereports', 'taxreport',
			'mc', 'large', 'sectioned', 'ciniki.sapos.invoicereports.taxreport');
		this.taxreport.year = null;
		this.taxreport.quarter = 0;
		this.taxreport.data = {};
		this.taxreport.sections = {
			'quarters':{'label':'', 'type':'simplegrid', 'num_cols':2,
				'noData':'No taxes found',
				},
		};
		this.taxreport.sectionData = function(s) {
			return this.data[s];
		};
        this.taxreport.headerValue = function(s, i, d) {
            if( i == 0 ) { return 'Quarter'; }
            if( i > 1 && i == (this.sections.quarters.num_cols - 1) ) { return 'Total'; }
            return this.data.taxrates[(i-1)].name;
        };
		this.taxreport.cellValue = function(s, i, j, d) {
            if( j == 0 ) { return d.start_date + ' - ' + d.end_date; }
            if( j > 1 && j == (this.sections.quarters.num_cols - 1) ) { return d.total_amount_display; }
            return d.taxrates[this.data.taxrates[(j-1)].id].amount_display;
		};
        this.taxreport.setup = function() {
            if( this.data.taxrates.length > 1 ) {
                this.sections.quarters.num_cols
            } else {
                this.sections.quarters.num_cols = 2;
            }
        };
		this.taxreport.addClose('Back');

        //
        // Report for Ontario, Canada HST
        //
		this.ontariohst = new M.panel('Ontario HST',
			'ciniki_sapos_invoicereports', 'ontariohst',
			'mc', 'large', 'sectioned', 'ciniki.sapos.invoicereports.ontariohst');
		this.ontariohst.year = null;
		this.ontariohst.quarter = 0;
		this.ontariohst.data = {};
		this.ontariohst.sections = {
			'years':{'label':'', 'type':'paneltabs', 'selected':'', 'tabs':{}},
			'quarters':{'label':'', 'visible':'no', 'type':'paneltabs', 'selected':'1', 'tabs':{
				'1':{'label':'Jan-Mar', 'fn':'M.ciniki_sapos_invoicereports.showOntarioHST(null,null,1);'},
				'2':{'label':'Apr-Jun', 'fn':'M.ciniki_sapos_invoicereports.showOntarioHST(null,null,2);'},
				'3':{'label':'Jul-Sep', 'fn':'M.ciniki_sapos_invoicereports.showOntarioHST(null,null,3);'},
				'4':{'label':'Oct-Dec', 'fn':'M.ciniki_sapos_invoicereports.showOntarioHST(null,null,4);'},
				}},
			'taxes':{'label':'', 'type':'simplegrid', 'num_cols':2,
				'sortable':'yes',
				'headerValues':['Invoice #', 'Date', 'Customer', 'Amount', 'Status'],
				'sortTypes':['number', 'date', 'text', 'number', 'text'],
				'noData':'No Invoices Found',
				},
		};
		this.ontariohst.sectionData = function(s) {
			return this.data[s];
		};
		this.ontariohst.cellValue = function(s, i, j, d) {
            switch(j) {
                case 0: return d.hst_line;
                case 1: return d.hst_value;
            }
		};
		this.ontariohst.addClose('Back');
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
		var appContainer = M.createContainer(appPrefix, 'ciniki_sapos_invoicereports', 'yes');
		if( appContainer == null ) {
			alert('App Error');
			return false;
		} 

        if( args.report != null && args.report != '' ) {
            this.reportShow(cb, args.report);
        }
	};

    this.reportShow = function(cb, report) {
        var method = '';
        switch (report) {
            case 'taxreport': method = 'ciniki.sapos.reportInvoicesTaxes'; break;
        }
		M.api.getJSONCb(method, {'business_id':M.curBusinessID}, function(rsp) {
            if( rsp.stat != 'ok' ) {
                M.api.err(rsp);
                return false;
            }
            var p = M.ciniki_sapos_invoicereports[report];
            p.data = rsp;
            p.refresh();
            p.show(cb);
        });
	};

	this.downloadExcel = function() {
		var args = {'business_id':M.curBusinessID, 'output':'excel'};
		if( this.invoices.year != null ) { args.year = this.invoices.year; }
		if( this.invoices.month != null ) { args.month = this.invoices.month; }
		if( this.invoices.payment_status != null ) { args.payment_status = this.invoices.payment_status; }
		M.api.openFile('ciniki.sapos.invoiceList', args);
	};
}
