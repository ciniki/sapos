function ciniki_sapos_customer() {

    this.invoices = new M.panel('Customer Invoices',
        'ciniki_sapos_customer', 'invoices',
        'mc', 'large', 'sectioned', 'ciniki.sapos.customer.invoices');
    this.invoices.customer_id = 0;
    this.invoices.year = null;
    this.invoices.month = 0;
    this.invoices.status = 0;
    this.invoices.data = {};
    this.invoices.sections = {
//          'years':{'label':'', 'type':'paneltabs', 'selected':'', 'tabs':{}},
//          'months':{'label':'', 'visible':'no', 'type':'paneltabs', 'selected':'0', 'tabs':{
//              '0':{'label':'All', 'fn':'M.ciniki_sapos_invoices.invoices.open(null,null,0);'},
//              '1':{'label':'Jan', 'fn':'M.ciniki_sapos_invoices.invoices.open(null,null,1);'},
//              '2':{'label':'Feb', 'fn':'M.ciniki_sapos_invoices.invoices.open(null,null,2);'},
//              '3':{'label':'Mar', 'fn':'M.ciniki_sapos_invoices.invoices.open(null,null,3);'},
//              '4':{'label':'Apr', 'fn':'M.ciniki_sapos_invoices.invoices.open(null,null,4);'},
//              '5':{'label':'May', 'fn':'M.ciniki_sapos_invoices.invoices.open(null,null,5);'},
//              '6':{'label':'Jun', 'fn':'M.ciniki_sapos_invoices.invoices.open(null,null,6);'},
//              '7':{'label':'Jul', 'fn':'M.ciniki_sapos_invoices.invoices.open(null,null,7);'},
//              '8':{'label':'Aug', 'fn':'M.ciniki_sapos_invoices.invoices.open(null,null,8);'},
//              '9':{'label':'Sep', 'fn':'M.ciniki_sapos_invoices.invoices.open(null,null,9);'},
//              '10':{'label':'Oct', 'fn':'M.ciniki_sapos_invoices.invoices.open(null,null,10);'},
//              '11':{'label':'Nov', 'fn':'M.ciniki_sapos_invoices.invoices.open(null,null,11);'},
//              '12':{'label':'Dec', 'fn':'M.ciniki_sapos_invoices.invoices.open(null,null,12);'},
//              }},
        'customer':{'label':'Customer', 'type':'simplegrid', 'num_cols':2,
            'cellClasses':['label', ''],
            },
        'types':{'label':'', 'visible':'no', 'type':'paneltabs', 'selected':'0', 'tabs':{
            '0':{'label':'All', 'visible':'no', 'fn':'M.ciniki_sapos_customer.invoices.open(null,null,null,0);'},
            '10':{'label':'Invoices', 'visible':'no', 'fn':'M.ciniki_sapos_customer.invoices.open(null,null,null,10);'},
            '20':{'label':'Carts', 'visible':'no', 'fn':'M.ciniki_sapos_customer.invoices.open(null,null,null,20);'},
            '30':{'label':'POS', 'visible':'no', 'fn':'M.ciniki_sapos_customer.invoices.open(null,null,null,30);'},
            '40':{'label':'Orders', 'visible':'no', 'fn':'M.ciniki_sapos_customer.invoices.open(null,null,null,40);'},
            }},
        'payment_statuses':{'label':'', 'visible':'yes', 'type':'paneltabs', 'selected':'0', 'tabs':{
            '0':{'label':'All', 'fn':'M.ciniki_sapos_customer.invoices.open(null,null,null,null,0);'},
            '10':{'label':'Payment Required', 'fn':'M.ciniki_sapos_customer.invoices.open(null,null,null,null,10);'},
            '40':{'label':'Partial Payment', 'fn':'M.ciniki_sapos_customer.invoices.open(null,null,null,null,40);'},
            '50':{'label':'Paid', 'fn':'M.ciniki_sapos_customer.invoices.open(null,null,null,null,50);'},
            '55':{'label':'Refunded', 'fn':'M.ciniki_sapos_customer.invoices.open(null,null,null,null,55);'},
            }},
//          'statuses':{'label':'', 'visible':'yes', 'type':'paneltabs', 'selected':'0', 'tabs':{
//              '0':{'label':'All', 'fn':'M.ciniki_sapos_customer.invoices.open(null,null,null,null,0);'},
//              '20':{'label':'Payment Required', 'fn':'M.ciniki_sapos_customer.invoices.open(null,null,null,null,20);'},
//              '40':{'label':'Deposit', 'fn':'M.ciniki_sapos_customer.invoices.open(null,null,null,null,40);'},
//              '50':{'label':'Paid', 'fn':'M.ciniki_sapos_customer.invoices.open(null,null,null,null,50);'},
//              '55':{'label':'Refunded', 'fn':'M.ciniki_sapos_customer.invoices.open(null,null,null,null,55);'},
//              '60':{'label':'Void', 'fn':'M.ciniki_sapos_customer.invoices.open(null,null,null,null,60);'},
//              }},
        'invoices':{'label':'', 'type':'simplegrid', 'num_cols':4,
            'sortable':'yes',
            'headerValues':['Invoice #', 'Date', 'Amount', 'Status'],
            'sortTypes':['number', 'date', 'number', 'text'],
            'noData':'No Invoices Found',
            },
//          '_buttons':{'label':'', 'buttons':{
//              'excel':{'label':'Download Excel', 'fn':'M.ciniki_sapos_invoices.downloadExcel();'},
//              }},
//          'totals':{'label':'Totals', 'list':{
//              'num_invoices':{'label':'Number of Invoices'},
//              'total_amount':{'label':'Amount Invoiced'},
//              }},
    };
    this.invoices.footerValue = function(s, i, d) {
        if( s == 'invoices' && this.data.totals != null ) {
            switch(i) {
                case 0: return this.data.totals.num_invoices;
                case 1: return '';
                case 2: return (this.sections.invoices.num_cols == 4 ? this.data.totals.total_amount : '');
                case 3: return (this.sections.invoices.num_cols == 5 ? this.data.totals.total_amount : '');
            }
        }
        return null;
    };
    this.invoices.footerClass = function(s, i, d) {
        if( s == 'invoices' ) {
            if( i == 3 || i == 4 ) { return 'alignright'; }
        }
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
        if( s == 'customer' ) {
            switch(j) {
                case 0: return d.detail.label;
                case 1: return d.detail.value.replace(/\n/, '<br/>');
            }
        }
        if( s == 'invoices' && M.modFlagOn('ciniki.sapos', 0x20) ) {
            switch(j) {
                case 0: return d.invoice.invoice_number;
                case 1: return d.invoice.po_number;
                case 2: return d.invoice.invoice_date;
                case 3: return d.invoice.total_amount_display;
                case 4: return d.invoice.status_text;
            }
        } else if( s == 'invoices' ) {
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
            return 'M.startApp(\'ciniki.sapos.invoice\',null,\'M.ciniki_sapos_customer.invoices.open();\',\'mc\',{\'invoice_id\':\'' + d.invoice.id + '\'});';
        }
    };
    this.invoices.addClose('Back');
    this.invoices.open = function(cb, cid, year, month, pstatus) {
        if( cid != null ) { this.customer_id = cid; }
        if( pstatus != null ) { 
            this.payment_status = pstatus; 
            this.sections.payment_statuses.selected = pstatus;
        }
        M.api.getJSONCb('ciniki.sapos.invoiceList', {'tnid':M.curTenantID,
            'customer_id':this.customer_id, 'customer':'yes', 
            'payment_status':this.payment_status}, function(rsp) {
                if( rsp.stat != 'ok' ) {
                    M.api.err(rsp);
                    return false;
                }
                var p = M.ciniki_sapos_customer.invoices;
                p.data = rsp;
                if( M.modFlagOn('ciniki.sapos', 0x20) ) {
                    p.sections.invoices.num_cols = 5;
                    p.sections.invoices.headerValues = ['Invoice #', 'PO', 'Date', 'Amount', 'Status'];
                    p.sections.invoices.sortTypes = ['number', 'text', 'date', 'number', 'text'];
                } else {
                    p.sections.invoices.num_cols = 4;
                    p.sections.invoices.headerValues = ['Invoice #', 'Date', 'Amount', 'Status'];
                    p.sections.invoices.sortTypes = ['number', 'date', 'number', 'text'];
                }
                p.refresh();
                p.show(cb);
            });
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
        var appContainer = M.createContainer(appPrefix, 'ciniki_sapos_customer', 'yes');
        if( appContainer == null ) {
            alert('App Error');
            return false;
        } 

        //
        // Setup the invoice types
        //
        var ct = 0;
        var default_type = 0;
        if( (M.curTenant.modules['ciniki.sapos'].flags&0x01) > 0 ) {
            this.invoices.sections.types.tabs['10'].visible = 'yes';
            if( default_type != '' ) { default_type = 10; }
            ct++;
        } else {
            this.invoices.sections.types.tabs['10'].visible = 'no';
        }
        if( (M.curTenant.modules['ciniki.sapos'].flags&0x08) > 0 ) {
            this.invoices.sections.types.tabs['20'].visible = 'yes';
            if( default_type != '' ) { default_type = 20; }
            ct++;
        } else {
            this.invoices.sections.types.tabs['20'].visible = 'no';
        }
        if( (M.curTenant.modules['ciniki.sapos'].flags&0x10) > 0 ) {
            this.invoices.sections.types.tabs['30'].visible = 'yes';
            if( default_type != '' ) { default_type = 30; }
            ct++;
        } else {
            this.invoices.sections.types.tabs['30'].visible = 'no';
        }
        if( (M.curTenant.modules['ciniki.sapos'].flags&0x20) > 0 ) {
            this.invoices.sections.types.tabs['40'].visible = 'yes';
            if( default_type != '' ) { default_type = 40; }
            ct++;
        } else {
            this.invoices.sections.types.tabs['40'].visible = 'no';
        }

        if( ct > 1 ) {
            this.invoices.sections.types.visible = 'yes';
            this.default_type = 0; // Default to all for more than one type
        } else {
            this.invoices.sections.types.visible = 'no';
        }
    
        this.invoices.open(cb, args.customer_id);
    };


//  this.downloadExcel = function() {
//      var args = {'tnid':M.curTenantID, 'output':'excel'};
//      if( this.invoices.year != null ) { args.year = this.invoices.year; }
//      if( this.invoices.month != null ) { args.month = this.invoices.month; }
//      if( this.invoices.status != null ) { args.status = this.invoices.status; }
//      window.open(M.api.getUploadURL('ciniki.sapos.invoiceList', args));
//  };
}
