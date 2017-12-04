function ciniki_sapos_invoices() {
    this.init = function() {
        this.invoices = new M.panel('Invoices',
            'ciniki_sapos_invoices', 'invoices',
            'mc', 'large', 'sectioned', 'ciniki.sapos.invoices.invoices');
        this.invoices.year = null;
        this.invoices.month = 0;
        this.invoices.invoice_type = 0;
        this.invoices.payment_status = 0;
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
            'types':{'label':'', 'visible':'no', 'type':'paneltabs', 'selected':'0', 'tabs':{
                '0':{'label':'All', 'visible':'no', 'fn':'M.ciniki_sapos_invoices.showInvoices(null,null,null,0);'},
                '10':{'label':'Invoices', 'visible':'no', 'fn':'M.ciniki_sapos_invoices.showInvoices(null,null,null,10);'},
//              '11':{'label':'Monthly Invoices', 'visible':'no', 'fn':'M.ciniki_sapos_invoices.showInvoices(null,null,null,11);'},
//              '19':{'label':'Yearly Invoices', 'visible':'no', 'fn':'M.ciniki_sapos_invoices.showInvoices(null,null,null,19);'},
                '20':{'label':'Carts', 'visible':'no', 'fn':'M.ciniki_sapos_invoices.showInvoices(null,null,null,20);'},
                '30':{'label':'POS', 'visible':'no', 'fn':'M.ciniki_sapos_invoices.showInvoices(null,null,null,30);'},
                '40':{'label':'Orders', 'visible':'no', 'fn':'M.ciniki_sapos_invoices.showInvoices(null,null,null,40);'},
                '90':{'label':'Quotes', 'visible':'no', 'fn':'M.ciniki_sapos_invoices.showInvoices(null,null,null,90);'},
                }},
            'payment_statuses':{'label':'', 'visible':'yes', 'type':'paneltabs', 'selected':'0', 'tabs':{
                '0':{'label':'All', 'fn':'M.ciniki_sapos_invoices.showInvoices(null,null,null,null,0);'},
                '10':{'label':'Payment Required', 'fn':'M.ciniki_sapos_invoices.showInvoices(null,null,null,null,10);'},
                '40':{'label':'Partial Payment', 'fn':'M.ciniki_sapos_invoices.showInvoices(null,null,null,null,40);'},
                '50':{'label':'Paid', 'fn':'M.ciniki_sapos_invoices.showInvoices(null,null,null,null,50);'},
                '55':{'label':'Refund Required', 'fn':'M.ciniki_sapos_invoices.showInvoices(null,null,null,null,55);'},
                '60':{'label':'Refunded', 'fn':'M.ciniki_sapos_invoices.showInvoices(null,null,null,null,60);'},
                }},
//          'statuses':{'label':'', 'visible':'yes', 'type':'paneltabs', 'selected':'0', 'tabs':{
//              '0':{'label':'All', 'fn':'M.ciniki_sapos_invoices.showInvoices(null,null,null,null,0);'},
//              '10':{'label':'Entered', 'fn':'M.ciniki_sapos_invoices.showInvoices(null,null,null,null,20);'},
//              '40':{'label':'Deposit', 'fn':'M.ciniki_sapos_invoices.showInvoices(null,null,null,null,40);'},
//              '50':{'label':'Paid', 'fn':'M.ciniki_sapos_invoices.showInvoices(null,null,null,null,50);'},
//              '55':{'label':'Refunded', 'fn':'M.ciniki_sapos_invoices.showInvoices(null,null,null,null,55);'},
//              '60':{'label':'Void', 'fn':'M.ciniki_sapos_invoices.showInvoices(null,null,null,null,60);'},
//              }},
            'invoices':{'label':'', 'type':'simplegrid', 'num_cols':5,
                'sortable':'yes',
                'headerValues':['Invoice #', 'Date', 'Customer', 'Amount', 'Status'],
                'sortTypes':['number', 'date', 'text', 'number', 'text'],
                'noData':'No Invoices Found',
                },
            '_buttons':{'label':'', 'buttons':{
                'excel':{'label':'Download Excel', 'fn':'M.ciniki_sapos_invoices.downloadExcel();'},
                }},
//          'totals':{'label':'Totals', 'list':{
//              'num_invoices':{'label':'Number of Invoices'},
//              'total_amount':{'label':'Amount Invoiced'},
//              }},
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
//          if( s == 'totals' ) { return this.sections[s].list; }
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
                    case 2: return d.invoice.customer_display_name;
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
        this.invoices.addButton('add', 'Invoice', 'M.startApp(\'ciniki.sapos.invoice\',null,\'M.ciniki_sapos_invoices.showInvoices();\',\'mc\',{});');
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

        //
        // Setup the invoice types
        //
        var ct = 0;
        var default_type = 0;
        if( (M.curTenant.modules['ciniki.sapos'].flags&0x010000) > 0 ) {
            this.invoices.sections.types.tabs['90'].visible = 'yes';
            if( default_type != '' ) { default_type = 90; }
            ct++;
        } else {
            this.invoices.sections.types.tabs['90'].visible = 'no';
        }
        if( (M.curTenant.modules['ciniki.sapos'].flags&0x010000) > 0 ) {
            this.invoices.sections.types.tabs['90'].visible = 'yes';
            if( default_type != '' ) { default_type = 90; }
            ct++;
        } else {
            this.invoices.sections.types.tabs['90'].visible = 'no';
        }
        if( (M.curTenant.modules['ciniki.sapos'].flags&0x01) > 0 ) {
            this.invoices.sections.types.tabs['10'].visible = 'yes';
            if( default_type != '' ) { default_type = 10; }
            ct++;
//          if( (M.curTenant.modules['ciniki.sapos'].flags&0x1000) > 0 ) {
//              this.invoices.sections.types.tabs['11'].visible = 'yes';
//              ct++;
//              this.invoices.sections.types.tabs['19'].visible = 'yes';
//              ct++;
//          }
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
    
        if( args.invoice_type != null && args.invoice_type != '' ) {
            default_type = args.invoice_type;
        }

        M.api.getJSONCb('ciniki.sapos.invoiceStats', {'tnid':M.curTenantID}, function(rsp) {
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
            M.ciniki_sapos_invoices.showInvoices(cb, dt.getFullYear(), 0, default_type, 0);
        });
    };

    this.showInvoices = function(cb, year, month, type, pstatus) {
        if( year != null ) {
            this.invoices.year = year;
            this.invoices.sections.years.selected = year;
        }
        if( month != null ) {
            this.invoices.month = month;
            this.invoices.sections.months.selected = month;
        }
        if( type != null ) {
            this.invoices.invoice_type = type;
            this.invoices.sections.types.selected = type;
        }
        if( pstatus != null ) {
            this.invoices.payment_status = pstatus;
            this.invoices.sections.payment_statuses.selected = pstatus;
        }
        this.invoices.sections.months.visible = (this.invoices.month>0)?'yes':'yes';
        this.invoices.sections.types.visible = 'no';
        var tc = 0;
        this.invoices.sections.types.tabs['0'] = {'label':'All', 'fn':'M.ciniki_sapos_invoices.showInvoices(null,null,null,0);'};
        if( (M.curTenant.modules['ciniki.sapos'].flags&0x010000) > 0 ) {
            this.invoices.sections.types.tabs['90'] = {'label':'Quotes', 'fn':'M.ciniki_sapos_invoices.showInvoices(null,null,null,90);'};
            tc++;
        }
        if( (M.curTenant.modules['ciniki.sapos'].flags&0x01) > 0 ) {
            this.invoices.sections.types.tabs['10'] = {'label':'Invoices', 'fn':'M.ciniki_sapos_invoices.showInvoices(null,null,null,10);'};
            tc++;
//          if( (M.curTenant.modules['ciniki.sapos'].flags&0x1000) > 0 ) {
//              this.invoices.sections.types.tabs['11'] = {'label':'Monthly', 'fn':'M.ciniki_sapos_invoices.showInvoices(null,null,null,11);'};
//              tc++;
//              this.invoices.sections.types.tabs['19'] = {'label':'Yearly', 'fn':'M.ciniki_sapos_invoices.showInvoices(null,null,null,19);'};
//              tc++;
//          }
        }
        if( (M.curTenant.modules['ciniki.sapos'].flags&0x08) > 0 ) {
            this.invoices.sections.types.tabs['20'] = {'label':'Carts', 'fn':'M.ciniki_sapos_invoices.showInvoices(null,null,null,20);'};
            tc++;
        }
        if( (M.curTenant.modules['ciniki.sapos'].flags&0x10) > 0 ) {
            this.invoices.sections.types.tabs['30'] = {'label':'POS', 'fn':'M.ciniki_sapos_invoices.showInvoices(null,null,null,30);'};
            tc++;
        }
        if( (M.curTenant.modules['ciniki.sapos'].flags&0x10) > 0 ) {
            this.invoices.sections.types.tabs['40'] = {'label':'Orders', 'fn':'M.ciniki_sapos_invoices.showInvoices(null,null,null,40);'};
            tc++;
        }
        if( tc > 1 ) {
            this.invoices.sections.types.visible = 'yes';
        }
        M.api.getJSONCb('ciniki.sapos.invoiceList', {'tnid':M.curTenantID,
            'year':this.invoices.year, 'month':this.invoices.month,
            'payment_status':this.invoices.payment_status, 'type':this.invoices.invoice_type,
            'sort':'invoice_date'}, function(rsp) {
                if( rsp.stat != 'ok' ) {
                    M.api.err(rsp);
                    return false;
                }
                var p = M.ciniki_sapos_invoices.invoices;
                p.data.invoices = rsp.invoices;
                p.data.totals = rsp.totals;
                p.sections._buttons.buttons.excel.visible=(rsp.invoices.length>0)?'yes':'no';
//              p.sections.invoices.visible = (rsp.invoices.length > 0)?'yes':'no';
                p.refresh();
                p.show(cb);
            });
    };

    this.downloadExcel = function() {
        var args = {'tnid':M.curTenantID, 'output':'excel'};
        if( this.invoices.year != null ) { args.year = this.invoices.year; }
        if( this.invoices.month != null ) { args.month = this.invoices.month; }
        if( this.invoices.payment_status != null ) { args.payment_status = this.invoices.payment_status; }
        M.api.openFile('ciniki.sapos.invoiceList', args);
    };
}
