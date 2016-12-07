//
function ciniki_sapos_main() {
    this.init = function() {
        //
        // The menu panel
        //
        this.menu = new M.panel('Accounting',
            'ciniki_sapos_main', 'menu',
            'mc', 'large narrowaside', 'sectioned', 'ciniki.sapos.main.menu');
        this.menu.data = {'invoice_type':'invoices'};
        this.menu.invoice_type = 10;
        this.menu.year = new Date().getFullYear();
        this.menu.month = 0;
        this.menu.payment_status = 0;
        this.menu.formtab = 'invoices';
        this.menu.formtabs = {'label':'', 'visible':'no', 'tabs':{
            'invoices':{'label':'Invoices', 'visible':'no', 'fn':'M.ciniki_sapos_main.showMenu(null,"invoices");'},
            'monthlyinvoices':{'label':'Monthly', 'visible':'no', 'fn':'M.ciniki_sapos_main.showMenu(null,"monthlyinvoices");'},
            'quarterlyinvoices':{'label':'Quarterly', 'visible':'no', 'fn':'M.ciniki_sapos_main.showMenu(null,"quarterlyinvoices");'},
            'yearlyinvoices':{'label':'Yearly', 'visible':'no', 'fn':'M.ciniki_sapos_main.showMenu(null,"yearlyinvoices");'},
            'pos':{'label':'POS', 'visible':'no', 'fn':'M.ciniki_sapos_main.showMenu(null,"pos");'},
            'orders':{'label':'Orders', 'visible':'no', 'fn':'M.ciniki_sapos_main.showMenu(null,"orders");'},
            'carts':{'label':'Carts', 'visible':'no', 'fn':'M.ciniki_sapos_main.showMenu(null,"carts");'},
            'expenses':{'label':'Expenses', 'visible':'no', 'fn':'M.ciniki_sapos_main.showMenu(null,"expenses");'},
            'mileage':{'label':'Mileage', 'visible':'no', 'fn':'M.ciniki_sapos_main.showMenu(null,"mileage");'},
            'quotes':{'label':'Quotes', 'visible':'no', 'fn':'M.ciniki_sapos_main.showMenu(null,"quotes");'},
            }};
        this.menu.forms = {};
        this.menu.forms.invoices = {
            'invoice_actions':{'label':'', 'aside':'yes', 'list':{
                'add':{'label':'Add Invoice', 'fn':'M.startApp(\'ciniki.sapos.invoice\',null,\'M.ciniki_sapos_main.showMenu();\',\'mc\',{});'},
                'downloadexcel':{'label':'Download Excel', 'fn':'M.ciniki_sapos_main.downloadInvoicesExcel();'},
                }},
            'invoice_reports':{'label':'Reports', 'aside':'yes', 'visible':'no', 'list':{
                'taxreport':{'label':'Tax Report', 
                    'visible':function() {return (M.curBusiness.modules['ciniki.taxes']!=null?'yes':'no');},
                    'fn':'M.startApp(\'ciniki.sapos.invoicereports\',null,\'M.ciniki_sapos_main.showMenu();\',\'mc\',{\'report\':\'taxreport\'});',
                    },
                }},
            '_quickadd':{'label':'', 'visible':'no', 'buttons':{
                'quickadd':{'label':'Quick Invoice', 'fn':'M.startApp(\'ciniki.sapos.qi\',null,\'M.ciniki_sapos_main.showMenu();\');'},
                }},
            'invoice_search':{'label':'', 'type':'livesearchgrid', 'livesearchcols':5, 
                'headerValues':['Invoice #','Date','Customer','Amount','Status'],
                'hint':'Search invoice # or customer name', 
                'noData':'No Invoices Found',
                },
            'years':{'label':'', 'visible':'no', 'type':'paneltabs', 'selected':'', 'tabs':{}},
            'months':{'label':'', 'visible':'no', 'type':'paneltabs', 'selected':'0', 'tabs':{
                '0':{'label':'All', 'fn':'M.ciniki_sapos_main.showInvoices(null,null,0);'},
                '1':{'label':'Jan', 'fn':'M.ciniki_sapos_main.showInvoices(null,null,1);'},
                '2':{'label':'Feb', 'fn':'M.ciniki_sapos_main.showInvoices(null,null,2);'},
                '3':{'label':'Mar', 'fn':'M.ciniki_sapos_main.showInvoices(null,null,3);'},
                '4':{'label':'Apr', 'fn':'M.ciniki_sapos_main.showInvoices(null,null,4);'},
                '5':{'label':'May', 'fn':'M.ciniki_sapos_main.showInvoices(null,null,5);'},
                '6':{'label':'Jun', 'fn':'M.ciniki_sapos_main.showInvoices(null,null,6);'},
                '7':{'label':'Jul', 'fn':'M.ciniki_sapos_main.showInvoices(null,null,7);'},
                '8':{'label':'Aug', 'fn':'M.ciniki_sapos_main.showInvoices(null,null,8);'},
                '9':{'label':'Sep', 'fn':'M.ciniki_sapos_main.showInvoices(null,null,9);'},
                '10':{'label':'Oct', 'fn':'M.ciniki_sapos_main.showInvoices(null,null,10);'},
                '11':{'label':'Nov', 'fn':'M.ciniki_sapos_main.showInvoices(null,null,11);'},
                '12':{'label':'Dec', 'fn':'M.ciniki_sapos_main.showInvoices(null,null,12);'},
                }},
            'payment_statuses':{'label':'', 'visible':'yes', 'type':'paneltabs', 'selected':'0', 'tabs':{
                '0':{'label':'All', 'fn':'M.ciniki_sapos_main.showInvoices(null,null,null,null,0);'},
                '10':{'label':'Payment Required', 'fn':'M.ciniki_sapos_main.showInvoices(null,null,null,null,10);'},
                '40':{'label':'Partial Payment', 'fn':'M.ciniki_sapos_main.showInvoices(null,null,null,null,40);'},
                '50':{'label':'Paid', 'fn':'M.ciniki_sapos_main.showInvoices(null,null,null,null,50);'},
                '55':{'label':'Refund Required', 'fn':'M.ciniki_sapos_main.showInvoices(null,null,null,null,55);'},
                '60':{'label':'Refunded', 'fn':'M.ciniki_sapos_main.showInvoices(null,null,null,null,60);'},
                }},
            'invoices':{'label':'', 'type':'simplegrid', 'num_cols':5,
                'headerValues':['Invoice #','Date','Customer','Amount','Status'],
                'noData':'No Invoices',
                },
            };
        this.menu.forms.monthlyinvoices = {
            'monthlyinvoice_search':{'label':'', 'type':'livesearchgrid', 'livesearchcols':5, 
                'headerValues':['Invoice #','Date','Customer','Amount','Status'],
                'hint':'Search invoice # or customer name', 
                'noData':'No Invoices Found',
                },
            'invoices':{'label':'Monthly Recurring Invoices', 'type':'simplegrid', 'num_cols':5,
                'headerValues':['Invoice #','Date','Customer','Amount','Status'],
                'sortable':'yes',
                'sortTypes':['number', 'date', 'text', 'altnumber', 'altnumber'],
                'noData':'No Monthly Invoices',
                },
            };
        this.menu.forms.quarterlyinvoices = {
            'quarterlyinvoice_search':{'label':'', 'type':'livesearchgrid', 'livesearchcols':5, 
                'headerValues':['Invoice #','Date','Customer','Amount','Status'],
                'hint':'Search invoice # or customer name', 
                'noData':'No Invoices Found',
                },
            'invoices':{'label':'Monthly Recurring Invoices', 'type':'simplegrid', 'num_cols':5,
                'headerValues':['Invoice #','Date','Customer','Amount','Status'],
                'sortable':'yes',
                'sortTypes':['number', 'date', 'text', 'altnumber', 'altnumber'],
                'noData':'No Monthly Invoices',
                },
            };
        this.menu.forms.yearlyinvoices = {
            'yearlyinvoice_search':{'label':'', 'type':'livesearchgrid', 'livesearchcols':5, 
                'headerValues':['Invoice #','Date','Customer','Amount','Status'],
                'hint':'Search invoice # or customer name', 
                'noData':'No Invoices Found',
                },
            'invoices':{'label':'Yearly Recurring Invoices', 'type':'simplegrid', 'num_cols':5,
                'headerValues':['Invoice #','Date','Customer','Amount','Status'],
                'sortable':'yes',
                'sortTypes':['number', 'date', 'text', 'altnumber', 'altnumber'],
                'noData':'No Yearly Invoices',
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
            'order_search':{'label':'Purchase Orders', 'type':'livesearchgrid', 'livesearchcols':5, 
                'headerValues':['Invoice #','Date','Customer','Amount','Status'],
                'hint':'Search invoice # or customer name', 
                'noData':'No Invoices Found',
                },
//          'menu':{'label':'', 'list':{
//              'packlist':{'label':'Packing Required', 'fn':'M.startApp(\'ciniki.sapos.shipments\',null,\'M.ciniki_sapos_main.showMenu();\',\'mc\',{\'list\':\'packlist\'});'},
//              'pendship':{'label':'Shipping Required', 'fn':'M.startApp(\'ciniki.sapos.shipments\',null,\'M.ciniki_sapos_main.showMenu();\',\'mc\',{\'list\':\'pendship\'});'},
//              }},
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
        this.menu.forms.quotes = {
            'quote_actions':{'label':'', 'aside':'yes', 'list':{
                'add':{'label':'Add Quote', 'fn':'M.startApp(\'ciniki.sapos.invoice\',null,\'M.ciniki_sapos_main.showMenu();\',\'mc\',{\'invoice_type\':\'90\'});'},
                'more':{'label':'More', 'fn':'M.startApp(\'ciniki.sapos.invoices\',null,\'M.ciniki_sapos_main.showMenu();\',\'mc\',{\'invoice_type\':\'90\'});'},
                }},
            'quote_search':{'label':'', 'type':'livesearchgrid', 'livesearchcols':4, 
                'headerValues':['Quote #','Date','Customer','Amount'],
                'hint':'Search quote # or customer name', 
                'noData':'No Quotes Found',
                },
            'invoices':{'label':'Recent Quotes', 'type':'simplegrid', 'num_cols':4,
                'headerValues':['Quote #','Date','Customer','Amount'],
                'noData':'No Quotes',
                },
            };
        this.menu.liveSearchCb = function(s, i, v) {
            if( s == 'invoice_search' && v != '' ) {
                M.api.getJSONBgCb('ciniki.sapos.invoiceSearch', {'business_id':M.curBusinessID,
                    'start_needle':v, 'sort':'reverse', 'limit':'10'}, function(rsp) {
                        M.ciniki_sapos_main.menu.liveSearchShow('invoice_search',null,M.gE(M.ciniki_sapos_main.menu.panelUID + '_' + s), rsp.invoices);
                    });
            }
            if( s == 'monthlyinvoice_search' && v != '' ) {
                M.api.getJSONBgCb('ciniki.sapos.invoiceSearch', {'business_id':M.curBusinessID,
                    'start_needle':v, 'invoice_type':'11', 'sort':'reverse', 'limit':'10'}, function(rsp) {
                        M.ciniki_sapos_main.menu.liveSearchShow('invoice_search',null,M.gE(M.ciniki_sapos_main.menu.panelUID + '_' + s), rsp.invoices);
                    });
            }
            if( s == 'quarterlyinvoice_search' && v != '' ) {
                M.api.getJSONBgCb('ciniki.sapos.invoiceSearch', {'business_id':M.curBusinessID,
                    'start_needle':v, 'invoice_type':'16', 'sort':'reverse', 'limit':'10'}, function(rsp) {
                        M.ciniki_sapos_main.menu.liveSearchShow('invoice_search',null,M.gE(M.ciniki_sapos_main.menu.panelUID + '_' + s), rsp.invoices);
                    });
            }
            if( s == 'yearlyinvoice_search' && v != '' ) {
                M.api.getJSONBgCb('ciniki.sapos.invoiceSearch', {'business_id':M.curBusinessID,
                    'start_needle':v, 'invoice_type':'19', 'sort':'reverse', 'limit':'10'}, function(rsp) {
                        M.ciniki_sapos_main.menu.liveSearchShow('invoice_search',null,M.gE(M.ciniki_sapos_main.menu.panelUID + '_' + s), rsp.invoices);
                    });
            }
            else if( s == 'order_search' && v != '' ) {
                M.api.getJSONBgCb('ciniki.sapos.invoiceSearch', {'business_id':M.curBusinessID,
                    'start_needle':v, 'invoice_type':'40', 'sort':'reverse', 'limit':'10'}, function(rsp) {
                        M.ciniki_sapos_main.menu.liveSearchShow('order_search',null,M.gE(M.ciniki_sapos_main.menu.panelUID + '_' + s), rsp.invoices);
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
            else if( s == 'quote_search' && v != '' ) {
                M.api.getJSONBgCb('ciniki.sapos.invoiceSearch', {'business_id':M.curBusinessID,
                    'start_needle':v, 'sort':'reverse', 'invoice_type':'90', 'limit':'10'}, function(rsp) {
                        M.ciniki_sapos_main.menu.liveSearchShow('quote_search',null,M.gE(M.ciniki_sapos_main.menu.panelUID + '_' + s), rsp.invoices);
                    });
            }
        };
        this.menu.liveSearchResultValue = function(s, f, i, j, d) {
            if( s == 'invoice_search' || s == 'monthlyinvoice_search' || s == 'quarterlyinvoice_search' || s == 'yearlyinvoice_search' || s == 'order_search' ) { 
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
            else if( s == 'quote_search' ) { 
                switch (j) {
                    case 0: return d.invoice.invoice_number;
                    case 1: return d.invoice.invoice_date;
                    case 2: return d.invoice.customer_display_name;
                    case 3: return d.invoice.total_amount_display;
                }
            }
            return '';
        };
        this.menu.liveSearchResultRowFn = function(s, f, i, j, d) {
            if( s == 'invoice_search' || s == 'monthly_search' || s == 'quarterlyinvoice_search' || s == 'yearly_search' || s == 'order_search' || s == 'quote_search' ) {
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
        this.menu.cellSortValue = function(s, i, j, d) {
            if( s == 'invoices' ) {
                switch(j) {
                    case 0: return d.invoice.invoice_number;
                    case 1: return d.invoice.invoice_date;
                    case 2: return d.invoice.customer_display_name;
                    case 3: return d.invoice.total_amount;
                    case 4: return d.invoice.status;
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
        this.menu.footerValue = function(s, i, j, d) {
            if( s == 'invoices' && this.formtab == 'invoices' && this.data.totals != null ) {
                switch(i) {
                    case 0: return this.data.totals.num_invoices;
                    case 1: return '';
                    case 2: return '';
                    case 3: return this.data.totals.total_amount;
                    case 4: return '';
                }
            } 
            if( s == 'invoices' && this.formtab == 'monthlyinvoices' && this.data.totals != null && this.data.totals.total_amount != '$0.00' ) {
                if( i == 3 ) { return this.data.totals.total_amount + ' (' + this.data.totals.yearly_amount + ')';  }
                return '';
            }
            if( s == 'invoices' && this.formtab == 'quarterlyinvoices' && this.data.totals != null && this.data.totals.total_amount != '$0.00' ) {
                if( i == 3 ) { return this.data.totals.total_amount + ' (' + this.data.totals.yearly_amount + ')';  }
                return '';
            }
            if( s == 'invoices' && this.formtab == 'yearlyinvoices' && this.data.totals != null && this.data.totals.total_amount != '$0.00' ) {
                if( i == 3 ) { return this.data.totals.monthly_amount + ' (' + this.data.totals.total_amount + ')';  }
                return '';
            }
            return null;
        };
        this.menu.footerClass = function(s, i, d) {
            if( s == 'invoices' && i == 4 ) { return 'alignright'; }
            return '';
        };
        this.menu.addClose('Back');
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
        var appContainer = M.createContainer(appPrefix, 'ciniki_sapos_main', 'yes');
        if( appContainer == null ) {
            alert('App Error');
            return false;
        } 

        this.menu.forms.invoices._quickadd.visible = ((M.curBusiness.modules['ciniki.sapos'].flags&0x04)>0)?'yes':'no';
//        if( M.curBusiness.modules['ciniki.sapos'].settings['invoice-reports-taxes-ontario-hst'] ) {
        if( M.curBusiness.modules['ciniki.taxes'] != null ) {
            this.menu.forms.invoices.invoice_reports.visible = 'yes';     
        } else {
            this.menu.forms.invoices.invoice_reports.visible = 'no';     
        }
        
        var ct = 0;
        var sp = '';
        if( (M.curBusiness.modules['ciniki.sapos'].flags&0x01) > 0 ) {
            this.menu.formtabs.tabs.invoices.visible = 'yes';
            if( sp == '' ) { sp = 'invoices'; }
            ct++;
            if( (M.curBusiness.modules['ciniki.sapos'].flags&0x1000) > 0 ) {
                this.menu.formtabs.tabs.monthlyinvoices.visible = 'yes';
                ct++;
                this.menu.formtabs.tabs.quarterlyinvoices.visible = 'yes';
                ct++;
                this.menu.formtabs.tabs.yearlyinvoices.visible = 'yes';
                ct++;
            } else {
                this.menu.formtabs.tabs.monthlyinvoices.visible = 'no';
                this.menu.formtabs.tabs.quarterlyinvoices.visible = 'no';
                this.menu.formtabs.tabs.yearlyinvoices.visible = 'no';
            }
        } else {
            this.menu.formtabs.tabs.invoices.visible = 'no';
            this.menu.formtabs.tabs.monthlyinvoices.visible = 'no';
            this.menu.formtabs.tabs.quarterlyinvoices.visible = 'no';
            this.menu.formtabs.tabs.yearlyinvoices.visible = 'no';
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
        if( (M.curBusiness.modules['ciniki.sapos'].flags&0x08) > 0 ) {
            this.menu.formtabs.tabs.carts.visible = 'yes';
            if( sp == '' ) { sp = 'carts'; }
            ct++;
        } else {
            this.menu.formtabs.tabs.carts.visible = 'no';
        }
        var rbts = 0;
        var lbts = 0;
        if( (M.curBusiness.modules['ciniki.sapos'].flags&0x02) > 0 ) {
            this.menu.formtabs.tabs.expenses.visible = 'yes';
            if( sp == '' ) { sp = 'expenses'; }
            ct++;
        } else {
            this.menu.formtabs.tabs.expenses.visible = 'no';
        }
        if( (M.curBusiness.modules['ciniki.sapos'].flags&0x100) > 0 ) {
            this.menu.formtabs.tabs.mileage.visible = 'yes';
            if( sp == '' ) { sp = 'mileage'; }
            ct++;
        } else {
            this.menu.formtabs.tabs.mileage.visible = 'no';
        }
        if( (M.curBusiness.modules['ciniki.sapos'].flags&0x010000) > 0 ) {
            this.menu.formtabs.tabs.quotes.visible = 'yes';
            if( sp == '' ) { sp = 'quotes'; }
            ct++;
        } else {
            this.menu.formtabs.tabs.quotes.visible = 'no';
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

        this.menu.size = 'medium mediumflex';
        if( this.menu.formtab == 'invoices' ) {
            this.menu.invoice_type = 10;
            this.showInvoices(cb);
            this.menu.size = 'large narrowaside';
        }
        else if( this.menu.formtab == 'carts'
            || this.menu.formtab == 'pos'
            || this.menu.formtab == 'orders'
            || this.menu.formtab == 'quotes'
            ) {
            switch(this.menu.formtab) {
                case 'carts': this.menu.invoice_type = 20; break;
                case 'pos': this.menu.invoice_type = 30; break;
                case 'orders': this.menu.invoice_type = 40; break;
                case 'quotes': this.menu.invoice_type = 90; this.menu.size = 'medium narrowaside'; break;
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
        else if( this.menu.formtab == 'monthlyinvoices'
            || this.menu.formtab == 'quarterlyinvoices'
            || this.menu.formtab == 'yearlyinvoices'
            ) {
            switch(this.menu.formtab) {
                case 'monthlyinvoices': this.menu.invoice_type = 11; break;
                case 'quarterlyinvoices': this.menu.invoice_type = 16; break;
                case 'yearlyinvoices': this.menu.invoice_type = 19; break;
            }
            M.api.getJSONCb('ciniki.sapos.invoiceList', {'business_id':M.curBusinessID,
                'sort':'date', 'type':this.menu.invoice_type}, function(rsp) {
                    if( rsp.stat != 'ok' ) {
                        M.api.err(rsp);
                        return false;
                    }
                    var p = M.ciniki_sapos_main.menu;
                    p.data.invoices = rsp.invoices;
                    p.data.totals = rsp.totals;
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
    };

    this.showInvoices = function(cb, year, month, type, pstatus) {
        if( year != null ) {
            this.menu.year = year;
            this.menu.forms.invoices.years.selected = year;
        }
        if( month != null ) {
            this.menu.month = month;
            this.menu.forms.invoices.months.selected = month;
        }
        if( type != null ) {
            this.menu.invoice_type = type;
            this.menu.forms.invoices.types.selected = type;
        }
        if( pstatus != null ) {
            this.menu.payment_status = pstatus;
            this.menu.forms.invoices.payment_statuses.selected = pstatus;
        }
        this.menu.forms.invoices.months.visible = (this.menu.month>0)?'yes':'yes';
        M.api.getJSONCb('ciniki.sapos.invoiceList', {'business_id':M.curBusinessID, 'year':this.menu.year, 'month':this.menu.month, 'stats':'yes',
            'payment_status':this.menu.payment_status, 'type':this.menu.invoice_type, 'sort':'invoice_date'}, function(rsp) {
                if( rsp.stat != 'ok' ) {
                    M.api.err(rsp);
                    return false;
                }
                var p = M.ciniki_sapos_main.menu;
                p.data.invoices = rsp.invoices;
                p.data.totals = rsp.totals;
                if( rsp.stats != null && rsp.stats.min_invoice_date_year != null ) {
                    var year = new Date().getFullYear();
                    p.forms.invoices.years.tabs = {};
                    if( year != rsp.stats.min_invoice_date_year ) {
                        p.forms.invoices.years.visible = 'yes';
                    }
                    if( p.forms.invoices.years.selected == '' ) {
                        p.forms.invoices.years.selected = year;
                    }
//                    if( p.forms.invoices.years.selected < rsp.stats.min_invoice_date_year ) {
//                        p.forms.invoices.years.selected = rsp.stats.min_invoice_date_year;
//                    }
                    for(var i=rsp.stats.min_invoice_date_year;i<=year;i++) {
                        p.forms.invoices.years.tabs[i] = {'label':i, 'fn':'M.ciniki_sapos_main.showInvoices(null,' + i + ',null);'};
                    }

                }
                p.refresh();
                p.show(cb);
            });
    };

    this.downloadInvoicesExcel = function() {
        var args = {'business_id':M.curBusinessID, 'output':'excel'};
        if( this.menu.year != null ) { args.year = this.menu.year; }
        if( this.menu.month != null ) { args.month = this.menu.month; }
        if( this.menu.invoice_type != null ) { args.type = this.menu.invoice_type; }
        if( this.menu.payment_status != null ) { args.payment_status = this.menu.payment_status; }
        M.api.openFile('ciniki.sapos.invoiceList', args);
    };
}
