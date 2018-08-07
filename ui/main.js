//
function ciniki_sapos_main() {

    this.transactionTypes = {
        '10':'Deposit',
        '20':'Payment',
        '60':'Refund',
        };
    this.transactionSources = {
        '10':'Paypal',
        '20':'Square',
        '50':'Visa',
        '55':'Mastercard',
        '60':'Discover',
        '65':'Amex',
        '90':'Interac',
        '100':'Cash',
        '105':'Check',
        '110':'Email Transfer',
        '120':'Other',
        };

    this._tabs = {'label':'', 'visible':'no', 'selected':'invoices', 'cb':'',
        'tabs':{
            'invoices':{'label':'Invoices', 'visible':'no', 'fn':'M.ciniki_sapos_main.menu.open(M.ciniki_sapos_main._tabs.cb,"invoices");'},
            'monthlyinvoices':{'label':'Monthly', 'visible':'no', 'fn':'M.ciniki_sapos_main.menu.open(M.ciniki_sapos_main._tabs.cb,"monthlyinvoices");'},
            'quarterlyinvoices':{'label':'Quarterly', 'visible':'no', 'fn':'M.ciniki_sapos_main.menu.open(M.ciniki_sapos_main._tabs.cb,"quarterlyinvoices");'},
            'yearlyinvoices':{'label':'Yearly', 'visible':'no', 'fn':'M.ciniki_sapos_main.menu.open(M.ciniki_sapos_main._tabs.cb,"yearlyinvoices");'},
            'transactions':{'label':'Transactions', 'visible':'no', 'fn':'M.ciniki_sapos_main.transactions.open(M.ciniki_sapos_main._tabs.cb);'},
            'donations':{'label':'Donations', 'visible':'no', 'fn':'M.ciniki_sapos_main.donations.open(M.ciniki_sapos_main._tabs.cb);'},
            'categories':{'label':'Categories', 'visible':'no', 'fn':'M.ciniki_sapos_main.categories.open(M.ciniki_sapos_main._tabs.cb);'},
            'pos':{'label':'POS', 'visible':'no', 'fn':'M.ciniki_sapos_main.menu.open(M.ciniki_sapos_main._tabs.cb,"pos");'},
            'orders':{'label':'Orders', 'visible':'no', 'fn':'M.ciniki_sapos_main.menu.open(M.ciniki_sapos_main._tabs.cb,"orders");'},
            'carts':{'label':'Carts', 'visible':'no', 'fn':'M.ciniki_sapos_main.menu.open(M.ciniki_sapos_main._tabs.cb,"carts");'},
            'expenses':{'label':'Expenses', 'visible':'no', 'fn':'M.ciniki_sapos_main.expenses.open(M.ciniki_sapos_main._tabs.cb);'},
            'mileage':{'label':'Mileage', 'visible':'no', 'fn':'M.ciniki_sapos_main.mileage.open(M.ciniki_sapos_main._tabs.cb);'},
            'quotes':{'label':'Quotes', 'visible':'no', 'fn':'M.ciniki_sapos_main.quotes.open(M.ciniki_sapos_main._tabs.cb);'},
            'reports':{'label':'Reports', 'visible':'no', 'fn':'M.ciniki_sapos_main.reports.open(M.ciniki_sapos_main._tabs.cb,"taxes");'},
        },
    };

    this.yearSwitch = function(y) {
        this[this._years.panel].yearSwitch(y);
    }
    this._years = {'label':'', 'visible':'no', 'type':'paneltabs', 'selected':'', 'panel':'invoices',
        'tabs':{},
        };
    this._months = {'label':'', 'type':'paneltabs', 'selected':'0', 'panel':'invoices',
        'visible':function() { var s = M.ciniki_sapos_main._tabs.selected; return ((s == 'invoices' || s == 'transactions' || s == 'expenses' || s == 'quotes' || s == 'categories' || s == 'donations' ) ? 'yes' : 'no');},
        'tabs':{
            '0':{'label':'All', 'fn':'M.ciniki_sapos_main.monthSwitch(0);'},
            '1':{'label':'Jan', 'fn':'M.ciniki_sapos_main.monthSwitch(1);'},
            '2':{'label':'Feb', 'fn':'M.ciniki_sapos_main.monthSwitch(2);'},
            '3':{'label':'Mar', 'fn':'M.ciniki_sapos_main.monthSwitch(3);'},
            '4':{'label':'Apr', 'fn':'M.ciniki_sapos_main.monthSwitch(4);'},
            '5':{'label':'May', 'fn':'M.ciniki_sapos_main.monthSwitch(5);'},
            '6':{'label':'Jun', 'fn':'M.ciniki_sapos_main.monthSwitch(6);'},
            '7':{'label':'Jul', 'fn':'M.ciniki_sapos_main.monthSwitch(7);'},
            '8':{'label':'Aug', 'fn':'M.ciniki_sapos_main.monthSwitch(8);'},
            '9':{'label':'Sep', 'fn':'M.ciniki_sapos_main.monthSwitch(9);'},
            '10':{'label':'Oct', 'fn':'M.ciniki_sapos_main.monthSwitch(10);'},
            '11':{'label':'Nov', 'fn':'M.ciniki_sapos_main.monthSwitch(11);'},
            '12':{'label':'Dec', 'fn':'M.ciniki_sapos_main.monthSwitch(12);'},
        }};
    this.monthSwitch = function(m) {
        this[this._months.panel].monthSwitch(m);
    }

    //
    // The menu panel
    //
    this.menu = new M.panel('Accounting', 'ciniki_sapos_main', 'menu', 'mc', 'full', 'sectioned', 'ciniki.sapos.main.menu');
    this.menu.data = {'invoice_type':'invoices'};
    this.menu.invoice_type = 10;
    this.menu.payment_status = 0;
    this.menu.menutabs = this._tabs;
    this.menu.sections = {
//        '_quickadd':{'label':'', 'visible':'no', 'buttons':{
//            'quickadd':{'label':'Quick Invoice', 'fn':'M.startApp(\'ciniki.sapos.qi\',null,\'M.ciniki_sapos_main.menu.open();\');'},
//            }},
        'years':this._years,
        'months':this._months,
        'payment_statuses':{'label':'', 'type':'paneltabs', 'selected':'0', 
            'visible':function() { return (M.ciniki_sapos_main._tabs.selected == 'invoices' ? 'yes' : 'no');},
            'tabs':{
                '0':{'label':'All', 'fn':'M.ciniki_sapos_main.menu.invoices(null,null,null,null,0);'},
                '10':{'label':'Payment Required', 'fn':'M.ciniki_sapos_main.menu.invoices(null,null,null,null,10);'},
                '40':{'label':'Partial Payment', 'fn':'M.ciniki_sapos_main.menu.invoices(null,null,null,null,40);'},
                '50':{'label':'Paid', 'fn':'M.ciniki_sapos_main.menu.invoices(null,null,null,null,50);'},
                '55':{'label':'Refund Required', 'fn':'M.ciniki_sapos_main.menu.invoices(null,null,null,null,55);'},
                '60':{'label':'Refunded', 'fn':'M.ciniki_sapos_main.menu.invoices(null,null,null,null,60);'},
            }},
        'invoice_search':{'label':'', 'type':'livesearchgrid', 'livesearchcols':5, 
            'visible':function() { var s = M.ciniki_sapos_main._tabs.selected; return ((s == 'invoices' || s == 'monthlyinvoices' || s == 'quarterlyinvoices' || s == 'yearlyinvoices') ? 'yes' : 'no');},
            'headerValues':['Invoice #','Date','Customer','Amount','Status'],
            'headerClasses':['', '', '', 'alignright', 'alignright'],
            'cellClasses':['', '', '', 'alignright', 'alignright'],
            'hint':'Search invoice # or customer name', 
            'noData':'No Invoices Found',
            },
        'invoices':{'label':'', 'type':'simplegrid', 'num_cols':5,
            'headerValues':['Invoice #', 'Date', 'Customer', 'Amount', 'Status'],
            'headerClasses':['', '', '', 'alignright', 'alignright'],
            'cellClasses':['', '', '', 'alignright', 'alignright'],
            'sortable':'yes',
            'sortTypes':['text', 'date', 'text', 'number', 'text'],
            'noData':'No Invoices',
            },
        }
    this.menu.liveSearchCb = function(s, i, v) {
        if( s == 'invoice_search' && v != '' ) {
            M.api.getJSONBgCb('ciniki.sapos.invoiceSearch', {'tnid':M.curTenantID,
                'start_needle':v, 'sort':'reverse', 'limit':'10'}, function(rsp) {
                    M.ciniki_sapos_main.menu.liveSearchShow('invoice_search',null,M.gE(M.ciniki_sapos_main.menu.panelUID + '_' + s), rsp.invoices);
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
        return '';
    };
    this.menu.liveSearchResultRowFn = function(s, f, i, j, d) {
        if( s == 'invoice_search' ) {
            return 'M.startApp(\'ciniki.sapos.invoice\',null,\'M.ciniki_sapos_main.menu.open();\',\'mc\',{\'invoice_id\':\'' + d.invoice.id + '\'});';
        }
    };
    this.menu.sectionData = function(s) {
        if( s == 'invoices' || s == 'items' ) { return this.data[s]; }
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
        if( s == 'categories' ) {
            return d.category + (d.num_items > 0 ? ' <span class="count">' + d.num_items + '</span>' : '');
        }
        if( s == 'items' ) {
            if( j == 0 ) {
                return '<span class="subdue">' + (parseInt(i) + 1) + '</span>';
            }
            else if( j == 1 ) {
                if( d.code != null && d.code != '' ) {
                    return '<span class="maintext">' + d.code + '</span><span class="subtext">' + d.description + '</span>' + (d.notes!=null&&d.notes!=''?'<span class="subsubtext">'+d.notes+'</span>':'');
                }
                if( d.notes != null && d.notes != '' ) {
                    return '<span class="maintext">' + d.description + '</span><span class="subtext">' + d.notes + '</span>';
                }
                return d.description;
            }
            else if( j == 2 ) {
                var discount = '';
                if( d.discount_amount != 0) {
                    if( d.unit_discount_amount > 0 ) {
                        discount += '-' + ((d.quantity>0&&d.quantity!=1)?(d.quantity+'@'):'') + '$' + d.unit_discount_amount;
                    }
                    if( d.unit_discount_percentage > 0 ) {
                        if( discount != '' ) { discount += ', '; }
                        discount += '-' + d.unit_discount_percentage + '%';
                    }
                }
                if( (this.data.flags&0x01) > 0 ) {
                    return ((d.quantity>0&&d.quantity!=1)?(d.quantity+' @ '):'') + d.unit_discounted_amount_display;
                } else if( discount != '' ) {
                    return '<span class="maintext">' + ((d.quantity>0&&d.quantity!=1)?(d.quantity+' @ '):'') + d.unit_amount_display + '</span><span class="subtext">' + discount + ' (-' + d.discount_amount_display + ')</span>';
                } else {
                    return ((d.quantity>0&&d.quantity!=1)?(d.quantity+' @ '):'') + d.unit_amount_display;
                }
            }
            else if( j == 3 ) {
                return '<span class="maintext">' + d.total_amount_display + '</span><span class="subtext">' + ((d.taxtype_name!=null)?d.taxtype_name:'') + '</span>';
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
        if( s == 'invoices' || s == 'donations' ) {
            return 'M.startApp(\'ciniki.sapos.invoice\',null,\'M.ciniki_sapos_main.menu.open();\',\'mc\',{\'invoice_id\':\'' + d.invoice.id + '\'});';
        }
        return '';
    }
    this.menu.footerValue = function(s, i, j, d) {
        if( s == 'invoices' && M.ciniki_sapos_main._tabs.selected == 'invoices' && this.data.totals != null ) {
            switch(i) {
                case 0: return this.data.totals.num_invoices;
                case 1: return '';
                case 2: return '';
                case 3: return this.data.totals.total_amount;
                case 4: return '';
            }
        } 
        if( s == 'items' && M.ciniki_sapos_main._tabs.selected == 'categories' && this.data.totals != null ) {
            switch(i) {
                case 0: return this.data.totals.num_transactions;
                case 3: return this.data.totals.total;
            }
        }
        if( s == 'invoices' && M.ciniki_sapos_main._tabs.selected == 'monthlyinvoices' && this.data.totals != null && this.data.totals.total_amount != '$0.00' ) {
            if( i == 3 ) { return this.data.totals.total_amount + ' (' + this.data.totals.yearly_amount + ')';  }
            return '';
        }
        if( s == 'invoices' && M.ciniki_sapos_main._tabs.selected == 'quarterlyinvoices' && this.data.totals != null && this.data.totals.total_amount != '$0.00' ) {
            if( i == 3 ) { return this.data.totals.total_amount + ' (' + this.data.totals.yearly_amount + ')';  }
            return '';
        }
        if( s == 'invoices' && M.ciniki_sapos_main._tabs.selected == 'yearlyinvoices' && this.data.totals != null && this.data.totals.total_amount != '$0.00' ) {
            if( i == 3 ) { return this.data.totals.monthly_amount + ' (' + this.data.totals.total_amount + ')';  }
            return '';
        }
        return null;
    }
    this.menu.footerClass = function(s, i, d) {
        if( s == 'invoices' && i == 4 ) { return 'alignright'; }
        if( s == 'transactions' && i > 1 ) { return 'alignright'; }
        return '';
    }
    this.menu.yearSwitch = function(y) { this.invoices(null,y); }
    this.menu.monthSwitch = function(m) { this.invoices(null,null,m); }
    this.menu.open = function(cb, type) {
        this.delButton('add');
        this.delButton('download');
        this.addButton('add', 'Invoice', 'M.startApp(\'ciniki.sapos.invoice\',null,\'M.ciniki_sapos_main.menu.open();\',\'mc\',{});');

        this.sections.years.visible = 'no';
        this.size = 'full';
        this.sections.invoices.headerValues = ['Invoice #', 'Date', 'Customer', 'Amount', 'Status'];
        if( M.ciniki_sapos_main._tabs.selected == 'invoices' ) {
            this.invoice_type = 10;
            M.ciniki_sapos_main.menu.invoices(cb);
            this.addButton('download', 'Excel', 'M.ciniki_sapos_main.menu.downloadExcel();');
        }
        else if( M.ciniki_sapos_main._tabs.selected == 'carts' || M.ciniki_sapos_main._tabs.selected == 'pos' || M.ciniki_sapos_main._tabs.selected == 'orders' ) {
            switch(M.ciniki_sapos_main._tabs.selected) {
                case 'carts': 
                    this.sections.invoices.headerValues = ['Cart #', 'Date', 'Customer', 'Amount', 'Status'];
                    this.sections.invoices.noData = 'No open shopping carts';
                    M.ciniki_sapos_main.menu.invoices(cb,null,null,20, 0);
                    break;
                case 'pos': 
                    this.sections.invoices.headerValues = ['POS #', 'Date', 'Customer', 'Amount', 'Status'];
                    this.sections.invoices.noData = 'No open sales';
                    M.ciniki_sapos_main.menu.invoices(cb,null,null,30, 0);
                    break;
                case 'orders': 
                    this.sections.invoices.headerValues = ['Order #', 'Date', 'Customer', 'Amount', 'Status'];
                    this.sections.invoices.noData = 'No open orders';
                    M.ciniki_sapos_main.menu.invoices(cb,null,null,40, 0);
                    break;
            }
        } 
        else if( M.ciniki_sapos_main._tabs.selected == 'monthlyinvoices' || M.ciniki_sapos_main._tabs.selected == 'quarterlyinvoices' || M.ciniki_sapos_main._tabs.selected == 'yearlyinvoices') {
            switch(M.ciniki_sapos_main._tabs.selected) {
                case 'monthlyinvoices': this.invoice_type = 11; break;
                case 'quarterlyinvoices': this.invoice_type = 16; break;
                case 'yearlyinvoices': this.invoice_type = 19; break;
            }
            M.api.getJSONCb('ciniki.sapos.invoiceList', {'tnid':M.curTenantID, 'sort':'date', 'type':this.invoice_type}, function(rsp) {
                if( rsp.stat != 'ok' ) {
                    M.api.err(rsp);
                    return false;
                }
                var p = M.ciniki_sapos_main.menu;
                p.data.invoices = rsp.invoices;
                p.data.totals = rsp.totals;
                p.refresh();
                p.show(cb);
            });
        }
    }
    this.menu.invoices = function(cb, year, month, type, pstatus) {
        this.sections.years.panel = 'menu';
        this.sections.months.panel = 'menu';
        if( year != null ) { this.sections.years.selected = year; }
        if( month != null ) { this.sections.months.selected = month; }
        if( type != null ) { this.invoice_type = type; }
        if( pstatus != null ) {
            this.payment_status = pstatus;
            this.sections.payment_statuses.selected = pstatus;
        }
        var args = {'tnid':M.curTenantID, 
            'year':this.sections.years.selected, 
            'month':this.sections.months.selected, 
            'stats':'yes',
            'payment_status':this.payment_status, 
            'sort':'invoice_date',
            }; 
        if( this.invoice_type == 10 ) {
            args['types'] = '10,30';
        } else {
            args['type'] = this.invoice_type;
        }
        M.api.getJSONCb('ciniki.sapos.invoiceList', args, function(rsp) {
            if( rsp.stat != 'ok' ) {
                M.api.err(rsp);
                return false;
            }
            var p = M.ciniki_sapos_main.menu;
            p.data.invoices = rsp.invoices;
            p.data.totals = rsp.totals;
            if( rsp.stats != null && rsp.stats.min_invoice_date_year != null ) {
                var year = new Date().getFullYear();
                p.sections.years.tabs = {};
                p.sections.years.visible = 'no';
                if( year != rsp.stats.min_invoice_date_year ) {
                    p.sections.years.visible = 'yes';
                }
                if( p.sections.years.selected == '' ) {
                    p.sections.years.selected = year;
                }
                for(var i=rsp.stats.min_invoice_date_year;i<=year;i++) {
                    p.sections.years.tabs[i] = {'label':i, 'fn':'M.ciniki_sapos_main.menu.invoices(null,' + i + ',null);'};
                }
            }
            p.refresh();
            p.show(cb);
        });
    }
    this.menu.downloadExcel = function() {
        var args = {'tnid':M.curTenantID, 'output':'excel'};
        if( this.sections.years.selected != null ) { args.year = this.sections.years.selected; }
        if( this.sections.months.selected != null ) { args.month = this.sections.months.selected; }
        if( this.invoice_type != null ) { args.type = this.invoice_type; }
        if( this.payment_status != null ) { args.payment_status = this.payment_status; }
        M.api.openFile('ciniki.sapos.invoiceList', args);
    }
    this.menu.addClose('Back');

    //
    // The transactions list panel
    //
    this.transactions = new M.panel('Transactions', 'ciniki_sapos_main', 'transactions', 'mc', 'full', 'sectioned', 'ciniki.sapos.main.transactions');
    this.transactions.data = {};
    this.transactions.menutabs = this._tabs;
    this.transactions.sections = {
        'years':this._years,
        'months':this._months,
        'transactions':{'label':'', 'type':'simplegrid', 'num_cols':7,
            'visible':function() { return (M.ciniki_sapos_main._tabs.selected == 'transactions' ? 'yes' : 'no');},
            'headerValues':['Type', 'Date', 'Invoice #', 'Customer', 'Amount', 'Fees', 'Net', 'Status'],
            'headerClasses':['', '', '', '', 'alignright', 'alignright', 'alignright', 'alignright'],
            'cellClasses':['', '', '', '', 'alignright', 'alignright', 'alignright', 'alignright'],
            'sortable':'yes',
            'sortTypes':['text', 'date', 'number', 'text', 'number', 'number', 'number'],
            'noData':'No Invoices',
            },
        }
    this.transactions.noData = function(s) {
        return this.sections[s].noData;
    }
    this.transactions.cellValue = function(s, i, j, d) {
        if( s == 'transactions' ) {
            switch(j) {
                case 0: return d.transaction_type;
                case 1: return d.transaction_date;
                case 2: return d.invoice_number;
                case 3: return d.customer_display_name;
                case 4: return d.customer_amount_display;
                case 5: return d.transaction_fees_display;
                case 6: return d.tenant_amount_display;
                case 7: return d.status_text;
            }
        }
    }
    this.transactions.rowFn = function(s, i, d) {
        if( s == 'transactions' ) {
            return 'M.ciniki_sapos_main.transaction.open(\'M.ciniki_sapos_main.transactions.open();\',\'' + d.id + '\');';
        }
    }
    this.transactions.footerValue = function(s, i, j, d) {
        if( s == 'transactions' && M.ciniki_sapos_main._tabs.selected == 'transactions' && this.data.totals != null ) {
            switch(i) {
                case 0: return this.data.totals.num_transactions;
                case 1: return '';
                case 2: return '';
                case 3: return '';
                case 4: return this.data.totals.customer_amount_display;
                case 5: return this.data.totals.transaction_fees_display;
                case 6: return this.data.totals.tenant_amount_display;
                case 7: return '';
            }
        }
        return null;
    }
    this.transactions.footerClass = function(s, i, d) {
        if( s == 'transactions' && i > 1 ) { return 'alignright'; }
        return '';
    }
    this.transactions.yearSwitch = function(y) { this.open(null,y); }
    this.transactions.monthSwitch = function(m) { this.open(null,null,m); }
    this.transactions.open = function(cb, year, month) {
        this.sections.years.panel = 'transactions';
        this.sections.months.panel = 'transactions';
        if( year != null ) { this.sections.years.selected = year; }
        if( month != null ) { this.sections.months.selected = month; }
        M.api.getJSONCb('ciniki.sapos.transactionList', {'tnid':M.curTenantID, 'year':this.sections.years.selected, 'month':this.sections.months.selected, 'stats':'yes',
            'sort':'transaction_date'}, function(rsp) {
                if( rsp.stat != 'ok' ) {
                    M.api.err(rsp);
                    return false;
                }
                var p = M.ciniki_sapos_main.transactions;
                p.data.transactions = rsp.transactions;
                p.data.totals = rsp.totals;
                if( rsp.stats != null && rsp.stats.min_invoice_date_year != null ) {
                    var year = new Date().getFullYear();
                    p.sections.years.tabs = {};
                    if( year != rsp.stats.min_invoice_date_year ) {
                        p.sections.years.visible = 'yes';
                    }
                    if( p.sections.years.selected == '' ) {
                        p.sections.years.selected = year;
                    }
                    for(var i=rsp.stats.min_invoice_date_year;i<=year;i++) {
                        p.sections.years.tabs[i] = {'label':i, 'fn':'M.ciniki_sapos_main.transactions.open(null,' + i + ',0);'};
                    }
                }
                p.refresh();
                p.show(cb);
            });
    }
    this.transactions.addClose('Back');

    //
    // The transaction panel
    //
    this.transaction = new M.panel('Transaction', 'ciniki_sapos_main', 'transaction', 'mc', 'medium', 'sectioned', 'ciniki.sapos.main.transaction');
    this.transaction.transaction_id = 0;
    this.transaction.data = {};
    this.transaction.sections = {
        'details':{'label':'', 'fields':{
            'transaction_type':{'label':'Type', 'type':'toggle', 'default':'20', 'toggles':this.transactionTypes},
            'status':{'label':'Status', 'type':'toggle', 'toggles':{'40':'Completed', '60':'Deposited'},
                'visible':function() { return M.modFlagSet('ciniki.sapos', 0x080000); },
                },
            'transaction_date':{'label':'Date', 'type':'text', 'size':'medium'},
            'source':{'label':'Source', 'type':'select', 'options':this.transactionSources},
            'customer_amount':{'label':'Customer Amount', 'type':'text', 'size':'small'},
            'transaction_fees':{'label':'Fees', 'type':'text', 'size':'small'},
            'tenant_amount':{'label':'Tenant Amount', 'type':'text', 'size':'small'},
            }},
        '_notes':{'label':'Notes', 'fields':{
            'notes':{'label':'', 'hidelabel':'yes', 'type':'textarea', 'size':'small'},
            }},
        '_buttons':{'label':'', 'buttons':{
            'save':{'label':'Save', 'fn':'M.ciniki_sapos_main.transaction.save();'},
            'delete':{'label':'Delete', 'fn':'M.ciniki_sapos_main.transaction.remove(M.ciniki_sapos_main.transaction.transaction_id);'},
            }},
    }
    this.transaction.fieldValue = function(s, i, d) {
        if( this.data != null && this.data[i] != null ) { return this.data[i]; }
        return '';
    }
    this.transaction.fieldHistoryArgs = function(s, i) {
        return {'method':'ciniki.sapos.history', 'args':{'tnid':M.curTenantID,
            'object':'ciniki.sapos.transaction', 'object_id':this.transaction_id, 'field':i}};
    }
    this.transaction.open = function(cb, tid, inid, date, amount) {
        if( tid != null ) { this.transaction_id = tid; }
        if( inid != null ) { this.invoice_id = inid; }
        if( this.transaction_id > 0 ) {
            M.api.getJSONCb('ciniki.sapos.transactionGet', {'tnid':M.curTenantID, 'transaction_id':this.transaction_id}, function(rsp) {
                if( rsp.stat != 'ok' ) {
                    M.api.err(rsp);
                    return false;
                }
                var p = M.ciniki_sapos_main.transaction;
                p.data = rsp.transaction;
                p.sections._buttons.buttons.delete.visible='yes';
                p.refresh();
                p.show(cb);
            });
        } else {
            var p = M.ciniki_sapos_main.transaction;
            p.reset();
            p.data = {};
            if( date != null && date != '' ) {
                if( date == 'now' ) {
                    var dt = new Date();
                    p.data.transaction_date = M.dateFormat(dt) + ' ' + M.dateMake12hourTime2(dt);
                } else {
                    p.data.transaction_date = date;
                }
            }
            if( amount != null && amount != '' ) { p.data.customer_amount = amount;}
            p.sections._buttons.buttons.delete.visible='no';
            p.refresh();
            p.show(cb);
        }
    }
    this.transaction.save = function() {
        if( this.transaction_id > 0 ) {
            var c = this.serializeForm('no');
            if( c != '' ) {
                M.api.postJSONCb('ciniki.sapos.transactionUpdate', {'tnid':M.curTenantID,
                    'transaction_id':this.transaction_id}, c, function(rsp) {
                        if( rsp.stat != 'ok' ) {
                            M.api.err(rsp);
                            return false;
                        }
                        M.ciniki_sapos_main.transaction.close();
                    });
            } else {
                this.close();
            }
        } else {
            var c = this.serializeForm('yes');
            M.api.postJSONCb('ciniki.sapos.transactionAdd', {'tnid':M.curTenantID, 'invoice_id':this.invoice_id}, c, function(rsp) {
                if( rsp.stat != 'ok' ) {
                    M.api.err(rsp);
                    return false;
                }
                M.ciniki_sapos_main.transaction.close();
            });
        }
    }
    this.transaction.remove = function(tid) {
        if( tid <= 0 ) { return false; }
        if( confirm("Are you sure you want to remove this transaction?") ) {
            M.api.getJSONCb('ciniki.sapos.transactionDelete', {'tnid':M.curTenantID,
                'transaction_id':tid}, function(rsp) {
                    if( rsp.stat != 'ok' ) {
                        M.api.err(rsp);
                        return false;
                    }
                    M.ciniki_sapos_main.transaction.close();
                });
        }
    }
    this.transaction.addButton('save', 'Save', 'M.ciniki_sapos_main.transaction.save();');
    this.transaction.addClose('Cancel');

    //
    // The donations panel
    //
    this.donations = new M.panel('Transactions', 'ciniki_sapos_main', 'donations', 'mc', 'full', 'sectioned', 'ciniki.sapos.main.donations');
    this.donations.data = {};
    this.donations.menutabs = this._tabs;
    this.donations.sections = {
        'years':this._years,
        'months':this._months,
        'invoices':{'label':'', 'type':'simplegrid', 'num_cols':6,
            'headerValues':['Invoice #', 'Date', 'Customer', 'Amount', 'Status', 'Receipt'],
            'headerClasses':['', '', '', 'alignright', 'alignright'],
            'cellClasses':['', '', '', 'alignright', 'alignright'],
            'sortable':'yes',
            'sortTypes':['date', 'text', 'number', 'text', 'text', 'text', 'number'],
            'noData':'No donations',
            },
        }
    this.donations.noData = function(s) {
        return this.sections[s].noData;
    }
    this.donations.cellValue = function(s, i, j, d) {
        if( s == 'invoices' ) {
            switch(j) {
                case 0: return d.invoice_number;
                case 1: return d.invoice_date;
                case 2: return d.customer_display_name;
                case 3: return d.donation_amount_display;
                case 4: return d.status_text;
                case 5: return d.donationreceipt_status_text;
            }
        }
    }
    this.donations.rowFn = function(s, i, d) {
        if( s == 'invoices' ) {
            return 'M.startApp(\'ciniki.sapos.invoice\',null,\'M.ciniki_sapos_main.donations.open();\',\'mc\',{\'invoice_id\':\'' + d.id + '\'});';
        }
    }
    this.donations.footerValue = function(s, i, j, d) {
        if( s == 'invoices' && this.data.totals != null ) {
            switch(i) {
                case 0: return this.data.totals.num_invoices;
                case 1: return '';
                case 2: return '';
                case 3: return this.data.totals.donation_amount;
            }
        }
        return null;
    }
    this.donations.footerClass = function(s, i, d) {
        if( s == 'invoices' && i > 1 ) { return 'alignright'; }
        return '';
    }
    this.donations.yearSwitch = function(y) { this.open(null,y); }
    this.donations.monthSwitch = function(m) { this.open(null,null,m); }
    this.donations.open = function(cb, year, month) {
        this.sections.years.panel = 'donations';
        this.sections.months.panel = 'donations';
        if( year != null ) { this.sections.years.selected = year; }
        if( month != null ) { this.sections.months.selected = month; }
        M.api.getJSONCb('ciniki.sapos.donationList', {'tnid':M.curTenantID, 'year':this.sections.years.selected, 'month':this.sections.months.selected, 
            'types':'10,30', 'payment_status':'50', 'stats':'yes', 'sort':'invoice_date'}, function(rsp) {
                if( rsp.stat != 'ok' ) {
                    M.api.err(rsp);
                    return false;
                }
                var p = M.ciniki_sapos_main.donations;
                p.data.invoices = rsp.invoices;
                p.data.totals = rsp.totals;
                if( rsp.stats != null && rsp.stats.min_invoice_date_year != null ) {
                    var year = new Date().getFullYear();
                    p.sections.years.tabs = {};
                    if( year != rsp.stats.min_invoice_date_year ) {
                        p.sections.years.visible = 'yes';
                    }
                    if( p.sections.years.selected == '' ) {
                        p.sections.years.selected = year;
                    }
                    for(var i=rsp.stats.min_invoice_date_year;i<=year;i++) {
                        p.sections.years.tabs[i] = {'label':i, 'fn':'M.ciniki_sapos_main.donations.open(null,' + i + ',0);'};
                    }
                }
                p.refresh();
                p.show(cb);
            });
    }
    this.donations.addClose('Back');


    //
    // The categories panel
    //
    this.categories = new M.panel('Invoice Categories', 'ciniki_sapos_main', 'categories', 'mc', 'full', 'sectioned', 'ciniki.sapos.main.categories');
    this.categories.data = {};
    this.categories.menutabs = this._tabs;
    this.categories.sections = {
        'years':this._years,
        'months':this._months,
        'invoices':{'label':'', 'type':'simplegrid', 'num_cols':3,
            'sortable':'yes',
            'headerValues':[],
            'headerClasses':[],
            'cellClasses':[],
            'sortTypes':[],
            'noData':'No Invoices',
            },
        }
    this.categories.noData = function(s) {
        return this.sections[s].noData;
    }
    this.categories.headerValue = function(s, i, d) {
        if( i == 0 ) { return 'Invoice #'; }
        if( i == 1 ) { return 'Date'; }
        if( i == 2 ) { return 'Customer'; }
        if( i < this.sections[s].num_cols-1 ) {
            return this.categories[(i-3)].name;
        } else {
            return 'Total';
        }
    }
    this.categories.cellValue = function(s, i, j, d) {
        if( j == 0 ) { return d.invoice_number; }
        if( j == 1 ) { return d.invoice_date; }
        if( j == 2 ) { return d.customer_display_name; }
        if( j < this.sections[s].num_cols-1 ) {
            if( d.categories[this.categories[(j-3)].name] != null ) {
                return d.categories[this.categories[(j-3)].name].amount_display;
            }
            return '';
        } else {
            return d.total_amount_display;
        }
    }
    this.categories.rowFn = function(s, i, d) {
        if( s == 'invoices' ) {
            return 'M.ciniki_sapos_main.ic.open(\'M.ciniki_sapos_main.categories.open();\',\'' + d.id + '\');';
        }
    }
    this.categories.footerValue = function(s, i, j, d) {
        if( i == 0 ) { return this.data.totals.num_invoices; }
        if( i < 3 ) { return ''; }
        if( i < this.sections[s].num_cols-1 ) {
            return this.categories[(i-3)].total_amount_display;
        } else {
            return this.data.totals.total_amount;
        }
    }
    this.categories.footerClass = function(s, i, d) {
        if( s == 'invoices' && i > 1 ) { return 'alignright'; }
        return '';
    }
    this.categories.yearSwitch = function(y) { this.open(null,y); }
    this.categories.monthSwitch = function(m) { this.open(null,null,m); }
    this.categories.open = function(cb, year, month) {
        this.sections.years.panel = 'categories';
        this.sections.months.panel = 'categories';
        if( year != null ) { this.sections.years.selected = year; }
        if( month != null ) { this.sections.months.selected = month; }
        this.sections.years.visible = 'no';
        M.api.getJSONCb('ciniki.sapos.invoiceCategories', {'tnid':M.curTenantID, 'year':M.ciniki_sapos_main._years.selected, 'month':M.ciniki_sapos_main._months.selected, 'stats':'yes'}, function(rsp) {
            if( rsp.stat != 'ok' ) {
                M.api.err(rsp);
                return false;
            }
            var p = M.ciniki_sapos_main.categories;
            p.data = rsp;
            p.categories = rsp.categories;
            p.sections.invoices.headerValues = ['Invoice #', 'Date', 'Customer'];
            p.sections.invoices.headerClasses = ['', '', ''];
            p.sections.invoices.cellClasses = ['', '', ''];
            p.sections.invoices.sortTypes = ['number', 'date', 'text'];
            p.sections.invoices.num_cols = 3;
            for(var i in p.categories) {
                p.sections.invoices.headerValues.push(p.categories[i].name);
                p.sections.invoices.headerClasses.push('alignright');
                p.sections.invoices.cellClasses.push('alignright');
                p.sections.invoices.sortTypes.push('number');
                p.sections.invoices.num_cols++;
            }
            p.sections.invoices.headerValues.push('Total');
            p.sections.invoices.headerClasses.push('alignright');
            p.sections.invoices.cellClasses.push('alignright');
            p.sections.invoices.sortTypes.push('number');
            p.sections.invoices.num_cols++;
            p.data.totals = rsp.totals;
            if( rsp.stats != null && rsp.stats.min_invoice_date_year != null ) {
                p.sections.years.visible = 'yes';
                var year = new Date().getFullYear();
                p.sections.years.tabs = {};
                for(var i=rsp.stats.min_invoice_date_year;i<=year;i++) {
                    p.sections.years.tabs[i] = {'label':i, 'fn':'M.ciniki_sapos_main.yearSwitch(' + i + ');'};
                }
            }
            p.refresh();
            p.show(cb);
        });
    }
    this.categories.downloadExcel = function() {
        var args = {'tnid':M.curTenantID, 'output':'excel'};
        if( this.sections.years.selected != null ) { args.year = this.sections.years.selected; }
        if( this.sections.months.selected != null ) { args.month = this.sections.months.selected; }
        M.api.openFile('ciniki.sapos.invoiceCategories', args);
    }
    this.categories.addButton('download', 'Excel', 'M.ciniki_sapos_main.categories.downloadExcel();');
    this.categories.addClose('Back');

    //
    // The invoice categories panel, used to manage the categories invoice items are in
    //
    this.ic = new M.panel('Invoice', 'ciniki_sapos_main', 'ic', 'mc', 'large', 'sectioned', 'ciniki.sapos.main.ic');
    this.ic.invoice_id = 0;
    this.ic.data = {};
    this.ic.sections = {
        'details':{'label':'', 'list':{
            'invoice_number':{'label':'Invoice #'},
            'po_number':{'label':'PO #'},
            'invoice_date':{'label':'Invoice Date'},
            }},
        'customer_details':{'label':'', 'type':'simplegrid', 'num_cols':2,
//            'headerValues':['Item', 'Category'],
            'cellClasses':['label',''],
//            'addTxt':'Edit',
//            'addFn':'M.startApp(\'ciniki.customers.edit\',null,\'M.ciniki_sapos_invoice.showInvoice();\',\'mc\',{\'next\':\'M.ciniki_sapos_invoice.updateInvoiceCustomer\',\'customer_id\':M.ciniki_sapos_invoice.invoice.data.customer_id});',
//            'changeTxt':'Change customer',
//            'changeFn':'M.startApp(\'ciniki.customers.edit\',null,\'M.ciniki_sapos_invoice.showInvoice();\',\'mc\',{\'next\':\'M.ciniki_sapos_invoice.updateInvoiceCustomer\',\'customer_id\':0});',
            },
        'items':{'label':'Item Categories', 'fields':{}},
        '_buttons':{'label':'', 'buttons':{
            'save':{'label':'Save', 'fn':'M.ciniki_sapos_main.ic.save();'},
            }},
    }
    this.ic.liveSearchCb = function(s, i, v) {
        M.api.getJSONBgCb('ciniki.sapos.invoiceCategoriesSearch', {'tnid':M.curTenantID, 'field':i, 'start_needle':'', 'limit':15}, function(rsp) {
            M.ciniki_sapos_main.ic.liveSearchShow(s,i,M.gE(M.ciniki_sapos_main.ic.panelUID + '_' + i), rsp.categories);
        });
    }
    this.ic.liveSearchResultValue = function(s,f,i,j,d) {
        return d.name;
    }
    this.ic.liveSearchResultRowFn = function(s,f,i,j,d) {
        return 'M.ciniki_sapos_main.ic.updateField(\'' + s + '\', \'' + f + '\', \'' + M.eU(d.name) + '\');';
    }
    this.ic.updateField = function(s, f, v) {
        this.setFieldValue(f, M.dU(v));
        this.removeLiveSearch(s, f);
    }
    this.ic.listLabel = function(s, i, d) {
        return d.label;
    }
    this.ic.listValue = function(s, i, d) {
        if( i == 'invoice_number' ) {
            if( this.data.invoice_type == 11 ) {
                return 'Monthly <span class="subdue">[' + this.data['status_text'] + ']</span>';
            } else if( this.data.invoice_type == 16 ) {
                return 'Quarterly <span class="subdue">[' + this.data['status_text'] + ']</span>';
            } else if( this.data.invoice_type == 18 ) {
                return 'Yearly <span class="subdue">[' + this.data['status_text'] + ']</span>';
            } else if( this.data.invoice_type == 90 ) {
                return this.data[i];
            } 
            return this.data[i] + ' <span class="subdue">[' + this.data['status_text'] + ']</span>';
        }
    }
    this.ic.sectionData = function(s) {
        if( s == 'details' ) { return this.sections[s].list; }
        return this.data[s];
    }
    this.ic.fieldValue = function(s, i, d) {
        return d.value;
    }
    this.ic.cellValue = function(s, i, j, d) {
        if( s == 'customer_details' ) {
            switch (j) {
                case 0: return d.detail.label;
                case 1: return (d.detail.label == 'Email'?M.linkEmail(d.detail.value):d.detail.value);
            }
        }
    }
    this.ic.open = function(cb, iid) {
        if( iid != null ) { this.invoice_id = iid; }
        M.api.getJSONCb('ciniki.sapos.invoiceGet', {'tnid':M.curTenantID, 'invoice_id':this.invoice_id}, function(rsp) {
            if( rsp.stat != 'ok' ) {
                M.api.err(rsp);
                return false;
            }
            var p = M.ciniki_sapos_main.ic;
            p.data = rsp.invoice;
            p.sections.items.fields = {};
            if( rsp.invoice.items != null ) {
                for(var i in rsp.invoice.items) {
                    p.sections.items.fields['item_' + rsp.invoice.items[i].item.id] = {
                        'label':rsp.invoice.items[i].item.description, 
                        'type':'text', 'livesearch':'yes', 'livesearchempty':'yes',
                        'value':rsp.invoice.items[i].item.category,
                        };
                }
            }
            p.refresh();
            p.show(cb);
        });
    }
    this.ic.save = function() {
        var c = this.serializeForm('no');
        if( c != '' ) {
            M.api.postJSONCb('ciniki.sapos.invoiceCategoriesUpdate', {'tnid':M.curTenantID, 'invoice_id':this.invoice_id}, c, function(rsp) {
                if( rsp.stat != 'ok' ) {
                    M.api.err(rsp);
                    return false;
                }
                M.ciniki_sapos_main.ic.close();
            });
        } else {
            this.close();
        }
    }
    this.ic.addClose('Back');

    //
    // The expenses panel
    //
    this.expenses = new M.panel('Expenses', 'ciniki_sapos_main', 'expenses', 'mc', 'full', 'sectioned', 'ciniki.sapos.main.expenses');
    this.expenses.year = null;
    this.expenses.month = 0;
    this.expenses.categories = {};
    this.expenses.data = {};
    this.expenses.menutabs = this._tabs;
    this.expenses.sections = {
        'years':this._years,
        'months':this._months,
        'expenses':{'label':'', 'type':'simplegrid', 'num_cols':5,
            'sortable':'yes',
            'sortTypes':['date', 'alttext', 'number', 'number', 'number'],
            'noData':'No Expenses Found',
            },
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
    this.expenses.liveSearchCb = function(s, i, v) {
        if( s == 'search' && v != '' ) {
            M.api.getJSONBgCb('ciniki.sapos.expenseSearch', {'tnid':M.curTenantID,
                'start_needle':v, 'sort':'reverse', 'limit':'10'}, function(rsp) {
                    M.ciniki_sapos_main.expenses.liveSearchShow('search',null,M.gE(M.ciniki_sapos_main.expenses.panelUID + '_' + s), rsp.expenses);
                });
        } 
    };
    this.expenses.liveSearchResultValue = function(s, f, i, j, d) {
        if( s == 'search' ) { 
            switch (j) {
                case 0: return d.expense.name;
                case 1: return d.expense.invoice_date;
                case 2: return d.expense.total_amount_display;
            }
        }
        return '';
    };
    this.expenses.liveSearchResultRowFn = function(s, f, i, j, d) {
        if( s == 'search' ) {
            return 'M.startApp(\'ciniki.sapos.expense\',null,\'M.ciniki_sapos_main.expenses.open();\',\'mc\',{\'expense_id\':\'' + d.expense.id + '\'});';
        }
    };
    this.expenses.cellValue = function(s, i, j, d) {
        if( j == 0 ) { return d.expense.invoice_date; }
        if( j == 1 ) { 
            if( d.expense.description != '' ) {
                return '<span class="maintext">' + d.expense.name + '</span><span class="subtext">' + d.expense.description + '</span>'; 
            } else {
                return d.expense.name; 
            }
        }
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
    this.expenses.cellSortValue = function(s, i, j, d) {
        if( j == 1 ) { return d.expense.name + d.expense.description; }
    }
    this.expenses.headerClass = function(s, i) {
        if( i > 1 ) { return 'alignright'; }
    }
    this.expenses.cellClass = function(s, i, j, d) {
        if( j == 1 ) { return 'multiline'; }
        if( j > 1 ) { return 'alignright'; }
    };
    this.expenses.rowFn = function(s, i, d) {
        if( s == 'expenses' ) {
            return 'M.startApp(\'ciniki.sapos.expense\',null,\'M.ciniki_sapos_main.expenses.open();\',\'mc\',{\'expense_id\':\'' + d.expense.id + '\'});';
        }
    };
    this.expenses.yearSwitch = function(y) { this.open(null,y); }
    this.expenses.monthSwitch = function(m) { this.open(null,null,m); }
    this.expenses.open = function(cb, year, month) {
        this.sections.years.panel = 'expenses';
        this.sections.months.panel = 'expenses';
        if( year != null ) { this.sections.years.selected = year; }
        if( month != null ) { this.sections.months.selected = month; }
        this.sections.years.visible = 'no';
        M.api.getJSONCb('ciniki.sapos.expenseGrid', {'tnid':M.curTenantID, 'year':M.ciniki_sapos_main._years.selected, 'month':M.ciniki_sapos_main._months.selected, 'stats':'yes'}, function(rsp) {
            if( rsp.stat != 'ok' ) {
                M.api.err(rsp);
                return false;
            }
            var p = M.ciniki_sapos_main.expenses;
            p.data.expenses = rsp.expenses;
            p.categories = rsp.categories;
            p.sections.expenses.num_cols = rsp.categories.length + 3;
            p.sections.expenses.sortTypes = ['date', 'alttext'];
            for(i=0;i<rsp.categories.length;i++) {
                p.sections.expenses.sortTypes.push('number');
            }
            p.sections.expenses.sortTypes.push('number');
            p.data.totals = rsp.totals;
            if( rsp.stats != null && rsp.stats.min_invoice_date_year != null ) {
                p.sections.years.visible = 'yes';
                var year = new Date().getFullYear();
                p.sections.years.tabs = {};
                for(var i=rsp.stats.min_invoice_date_year;i<=year;i++) {
                    p.sections.years.tabs[i] = {'label':i, 'fn':'M.ciniki_sapos_main.yearSwitch(' + i + ');'};
                }
            }
            p.refresh();
            p.show(cb);
        });
    };
    this.expenses.download = function() {
        var args = {'tnid':M.curTenantID, 'output':'excel'};
        if( this.sections.years.selected != null ) { args.year = this.sections.years.selected; }
        if( this.sections.months.selected != null ) { args.month = this.sections.months.selected; }
        M.api.openFile('ciniki.sapos.expenseGrid', args);
    };
    this.expenses.addButton('add', 'Expense', 'M.startApp(\'ciniki.sapos.expense\',null,\'M.ciniki_sapos_main.expenses.open();\',\'mc\',{});');
    this.expenses.addButton('download', 'Excel', 'M.ciniki_sapos_main.expenses.download();');
    this.expenses.addClose('Back');

    //
    // The mileage panel
    //
    this.mileage = new M.panel('Mileage', 'ciniki_sapos_main', 'mileage', 'mc', 'large', 'sectioned', 'ciniki.sapos.main.mileage');
    this.mileage.year = null;
    this.mileage.month = 0;
    this.mileage.data = {};
    this.mileage.menutabs = this._tabs;
    this.mileage.sections = {
        'years':this._years,
        'months':this._months,
        'search':{'label':'Mileage', 'type':'livesearchgrid', 'livesearchcols':3, 
            'headerValues':['Date', 'From/To', 'Distance'],
            'hint':'Search mileage', 
            'noData':'No Mileage Entries Found',
            },
        'mileages':{'label':'Recent Mileage', 'type':'simplegrid', 'num_cols':4,
            'headerValues':['Date', 'From/To', 'Distance', 'Amount'],
            'noData':'No Mileage',
            'addTxt':'More',
            'addFn':'M.startApp(\'ciniki.sapos.mileages\',null,\'M.ciniki_sapos_main.mileage.open();\');',
            },
        '_buttons':{'label':'', 'visible':'no', 
            'visible':function() { return (M.ciniki_sapos_main._tabs.selected == 'mileage' ? 'yes' : 'no');},
            'buttons':{
                'settings':{'label':'Setup Mileage', 'visible':'no', 'fn':'M.startApp(\'ciniki.sapos.settings\',null,\'M.ciniki_sapos_main.mileage.open();\',\'mc\',{\'mrates\':\'yes\'});'},
            }},
    };
    this.mileage.liveSearchCb = function(s, i, v) {
        if( s == 'search' && v != '' ) {
            M.api.getJSONBgCb('ciniki.sapos.mileageSearch', {'tnid':M.curTenantID,
                'start_needle':v, 'sort':'reverse', 'limit':'10'}, function(rsp) {
                    M.ciniki_sapos_main.mileage.liveSearchShow('search',null,M.gE(M.ciniki_sapos_main.mileage.panelUID + '_' + s), rsp.mileages);
                });
        }
    };
    this.mileage.liveSearchResultValue = function(s, f, i, j, d) {
        if( s == 'search' ) { 
            switch (j) {
                case 0: return d.mileage.travel_date;
                case 1: return d.mileage.start_name + ' - ' + d.mileage.end_name;
                case 2: return d.mileage.total_distance + ' ' + d.mileage.units;
                case 3: return d.mileage.amount_display;
            }
        }
        return '';
    };
    this.mileage.liveSearchResultRowFn = function(s, f, i, j, d) {
        if( s == 'search' ) {
            return 'M.startApp(\'ciniki.sapos.mileage\',null,\'M.ciniki_sapos_main.mileage.open();\',\'mc\',{\'mileage_id\':\'' + d.mileage.id + '\'});';
        }
    };
    this.mileage.cellValue = function(s, i, j, d) {
        if( s == 'mileages' ) {
            switch(j) {
                case 0: return d.mileage.travel_date;
                case 1: return d.mileage.start_name + ' - ' + d.mileage.end_name;
                case 2: return d.mileage.total_distance + ' ' + d.mileage.units;
                case 3: return d.mileage.amount_display;
            }
        }
    };
    this.mileage.yearSwitch = function(y) { this.open(null,y); }
    this.mileage.monthSwitch = function(m) { this.open(null,null,m); }
    this.mileage.open = function(cb, year, month) {
        this.sections.years.panel = 'expenses';
        this.sections.months.panel = 'expenses';
        if( year != null ) { this.sections.years.selected = year; }
        if( month != null ) { this.sections.months.selected = month; }
        M.api.getJSONCb('ciniki.sapos.latest', {'tnid':M.curTenantID, 'limit':'10', 'sort':'latest', 'type':'mileage'}, function(rsp) {
            if( rsp.stat != 'ok' ) {
                M.api.err(rsp);
                return false;
            }
            var p = M.ciniki_sapos_main.mileage;
            p.data.mileages = rsp.mileages;
            if( rsp.mileages.length > 0 ) {
                p.sections.search.visible = 'yes';
                p.sections.mileages.visible = 'yes';
                p.sections._buttons.visible = 'no';
                p.sections._buttons.buttons.settings.visible = 'no';
            } else {
                p.sections.search.visible = 'no';
                p.sections.mileages.visible = 'no';
                p.sections._buttons.visible = 'yes';
                p.sections._buttons.buttons.settings.visible = 'yes';
            }
            p.refresh();
            p.show(cb);
        });
    }
    this.mileage.addClose('Back');
       
    //
    // The quotes panel
    //
    this.quotes = new M.panel('Quotes', 'ciniki_sapos_main', 'quotes', 'mc', 'full', 'sectioned', 'ciniki.sapos.main.quotes');
    this.quotes.year = null;
    this.quotes.month = 0;
    this.quotes.data = {};
    this.quotes.menutabs = this._tabs;
    this.quotes.sections = {
        'years':this._years,
        'months':this._months,
        'search':{'label':'', 'type':'livesearchgrid', 'livesearchcols':3, 
            'headerValues':['Date', 'From/To', 'Distance'],
            'hint':'Search quotes', 
            'noData':'No quotes found',
            },
        'invoices':{'label':'', 'type':'simplegrid', 'num_cols':5,
            'headerValues':['Quote #', 'Date', 'Customer', 'Amount', 'Status'],
            'headerClasses':['', '', '', 'alignright', 'alignright'],
            'cellClasses':['', '', '', 'alignright', 'alignright'],
            'sortable':'yes',
            'sortTypes':['text', 'date', 'text', 'number', 'text'],
            'noData':'No quotes',
            },
    };
    this.quotes.liveSearchCb = function(s, i, v) {
        if( s == 'search' && v != '' ) {
            M.api.getJSONBgCb('ciniki.sapos.invoiceSearch', {'tnid':M.curTenantID,
                'start_needle':v, 'sort':'reverse', 'invoice_type':'90', 'limit':'10'}, function(rsp) {
                    M.ciniki_sapos_main.quotes.liveSearchShow('search',null,M.gE(M.ciniki_sapos_main.quotes.panelUID + '_' + s), rsp.invoices);
                });
        }
    };
    this.quotes.liveSearchResultValue = function(s, f, i, j, d) {
        if( s == 'search' ) { 
            switch (j) {
                case 0: return d.invoice.invoice_number;
                case 1: return d.invoice.invoice_date;
                case 2: return d.invoice.customer_display_name;
                case 3: return d.invoice.total_amount_display;
            }
        } 
        return '';
    };
    this.quotes.liveSearchResultRowFn = function(s, f, i, j, d) {
        if( s == 'search' ) {
            return 'M.startApp(\'ciniki.sapos.invoice\',null,\'M.ciniki_sapos_main.quotes.open();\',\'mc\',{\'invoice_id\':\'' + d.invoice.id + '\'});';
        }
    };
    this.quotes.noData = function(s) {
        return this.sections[s].noData;
    };
    this.quotes.cellValue = function(s, i, j, d) {
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
    this.quotes.cellSortValue = function(s, i, j, d) {
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
    this.quotes.rowFn = function(s, i, d) {
        if( s == 'invoices' ) {
            return 'M.startApp(\'ciniki.sapos.invoice\',null,\'M.ciniki_sapos_main.quotes.open();\',\'mc\',{\'invoice_id\':\'' + d.invoice.id + '\'});';
        }
        return '';
    }
    this.quotes.footerValue = function(s, i, j, d) {
        if( s == 'invoices' && this.data.totals != null ) {
            switch(i) {
                case 0: return this.data.totals.num_invoices;
                case 1: return '';
                case 2: return '';
                case 3: return this.data.totals.total_amount;
                case 4: return '';
            }
        } 
        return null;
    }
    this.quotes.footerClass = function(s, i, d) {
        if( s == 'invoices' && i == 4 ) { return 'alignright'; }
        return '';
    }
    this.quotes.yearSwitch = function(y) { this.open(null,y); }
    this.quotes.monthSwitch = function(m) { this.open(null,null,m); }
    this.quotes.open = function(cb, year, month) {
        this.sections.years.panel = 'quotes';
        this.sections.months.panel = 'quotes';
        if( year != null ) { this.sections.years.selected = year; }
        if( month != null ) { this.sections.months.selected = month; }
        M.api.getJSONCb('ciniki.sapos.invoiceList', {'tnid':M.curTenantID, 'year':this.sections.years.selected, 'month':this.sections.months.selected, 'stats':'yes',
            'sort':'invoice_date', 'type':90}, function(rsp) {
                if( rsp.stat != 'ok' ) {
                    M.api.err(rsp);
                    return false;
                }
                var p = M.ciniki_sapos_main.quotes;
                p.data.invoices = rsp.invoices;
                p.data.totals = rsp.totals;
                if( rsp.stats != null && rsp.stats.min_invoice_date_year != null ) {
                    var year = new Date().getFullYear();
                    p.sections.years.tabs = {};
                    p.sections.years.visible = 'no';
                    if( year != rsp.stats.min_invoice_date_year ) {
                        p.sections.years.visible = 'yes';
                    }
                    if( p.sections.years.selected == '' ) {
                        p.sections.years.selected = year;
                    }
                    for(var i=rsp.stats.min_invoice_date_year;i<=year;i++) {
                        p.sections.years.tabs[i] = {'label':i, 'fn':'M.ciniki_sapos_main.yearSwitch(' + i + ');'};
                    }
                }
                p.refresh();
                p.show(cb);
            });
    }
    this.quotes.addButton('add', 'Quote', 'M.startApp(\'ciniki.sapos.invoice\',null,\'M.ciniki_sapos_main.quotes.open();\',\'mc\',{\'type\':\'90\'});');

    //
    // The reports panel
    //
    this.reports = new M.panel('Reports', 'ciniki_sapos_main', 'reports', 'mc', 'large', 'sectioned', 'ciniki.sapos.main.reports');
    this.reports.year = null;
    this.reports.quarter = 0;
    this.reports.data = {};
    this.reports.menutabs = this._tabs;
    this.reports.cols = [];
    this.reports.sections = {
        'quarters':{'label':'', 'type':'simplegrid', 'num_cols':2,
            'cols':{},
            'noData':'No taxes found',
            },
    }
    this.reports.sectionData = function(s) {
        return this.data[s];
    }
    this.reports.headerValue = function(s, i, d) {
        if( i == 0 ) { return 'Quarter'; }
        if( i > 1 && i == (this.sections.quarters.num_cols - 1) ) { return 'Total'; }
        return this.sections.quarters.cols[(i-1)].name;
    }
    this.reports.cellValue = function(s, i, j, d) {
        if( j == 0 ) { return d.start_date + ' - ' + d.end_date; }
        if( j > 1 && j == (this.sections.quarters.num_cols - 1) ) { return d.total_amount_display; }
        var c = this.sections.quarters.cols[(j-1)];
        return d[c.type][c.id].amount_display;
    }
    this.reports.setup = function() {
    }
    this.reports.open = function(cb, report) {
        var method = '';
        switch (report) {
            case 'taxes': method = 'ciniki.sapos.reportInvoicesTaxes'; break;
        }
        M.api.getJSONCb(method, {'tnid':M.curTenantID}, function(rsp) {
            if( rsp.stat != 'ok' ) {
                M.api.err(rsp);
                return false;
            }
            var p = M.ciniki_sapos_main.reports;
            p.data = rsp;
            p.sections.quarters.cols = {};
            var c = 0;
            p.sections.quarters.num_cols = 1;
            for(i in p.data.taxrates) {
                p.sections.quarters.cols[c] = {'type':'taxrates', 'id':p.data.taxrates[i].id, 'name':p.data.taxrates[i].name};
                c++;
            }
            for(i in p.data.expensecategories) {
                p.sections.quarters.cols[c] = {'type':'expenses', 'id':p.data.expensecategories[i].id, 'name':p.data.expensecategories[i].name};
                c++;
            }
            p.sections.quarters.num_cols += c;
            if( c > 1 ) {
                // Add extra column for totals
                p.sections.quarters.num_cols++;
            }
            p.refresh();
            p.show(cb);
        });
    }
    this.reports.addClose('Back');

    //
    // Report for Ontario, Canada HST
    //
    this.ontariohst = new M.panel('Ontario HST',
        'ciniki_sapos_main', 'ontariohst',
        'mc', 'large', 'sectioned', 'ciniki.sapos.main.ontariohst');
    this.ontariohst.year = null;
    this.ontariohst.quarter = 0;
    this.ontariohst.data = {};
    this.ontariohst.sections = {
        'years':{'label':'', 'type':'paneltabs', 'selected':'', 'tabs':{}},
        'quarters':{'label':'', 'visible':'no', 'type':'paneltabs', 'selected':'1', 'tabs':{
            '1':{'label':'Jan-Mar', 'fn':'M.ciniki_sapos_main.showOntarioHST(null,null,1);'},
            '2':{'label':'Apr-Jun', 'fn':'M.ciniki_sapos_main.showOntarioHST(null,null,2);'},
            '3':{'label':'Jul-Sep', 'fn':'M.ciniki_sapos_main.showOntarioHST(null,null,3);'},
            '4':{'label':'Oct-Dec', 'fn':'M.ciniki_sapos_main.showOntarioHST(null,null,4);'},
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

        this._tabs.tabs.reports.visible = 'no';
        if( M.curTenant.modules['ciniki.taxes'] != null ) {
            this._tabs.tabs.reports.visible = 'yes';
        }
        
        var ct = 0;
        var sp = '';
        if( M.modFlagOn('ciniki.sapos', 0x01) ) {
            this._tabs.tabs.invoices.visible = 'yes';
            this._tabs.tabs.transactions.visible = 'yes';
            if( sp == '' ) { sp = 'invoices'; }
            ct+=2;
            if( M.modFlagOn('ciniki.sapos', 0x1000) ) {
                this._tabs.tabs.monthlyinvoices.visible = 'yes';
                this._tabs.tabs.quarterlyinvoices.visible = 'yes';
                this._tabs.tabs.yearlyinvoices.visible = 'yes';
                ct+=3;
            } else {
                this._tabs.tabs.monthlyinvoices.visible = 'no';
                this._tabs.tabs.quarterlyinvoices.visible = 'no';
                this._tabs.tabs.yearlyinvoices.visible = 'no';
            }
        } else {
            this._tabs.tabs.invoices.visible = 'no';
            this._tabs.tabs.monthlyinvoices.visible = 'no';
            this._tabs.tabs.quarterlyinvoices.visible = 'no';
            this._tabs.tabs.yearlyinvoices.visible = 'no';
        }
        if( M.modFlagOn('ciniki.sapos', 0x10) ) {
            this._tabs.tabs.pos.visible = 'yes';
            if( sp == '' ) { sp = 'pos'; }
            ct++;
        } else {
            this._tabs.tabs.pos.visible = 'no';
        } 
        if( M.modFlagOn('ciniki.sapos', 0x20) ) {
            this._tabs.tabs.orders.visible = 'yes';
            if( sp == '' ) { sp = 'orders'; }
            ct++;
        } else {
            this._tabs.tabs.orders.visible = 'no';
        }
        if( M.modFlagOn('ciniki.sapos', 0x08) ) {
            this._tabs.tabs.carts.visible = 'yes';
            if( sp == '' ) { sp = 'carts'; }
            ct++;
        } else {
            this._tabs.tabs.carts.visible = 'no';
        }
        if( M.modFlagOn('ciniki.sapos', 0x01000000) ) {
            this._tabs.tabs.categories.visible = 'yes';
            if( sp == '' ) { sp = 'categories'; }
            ct++;
        } else {
            this._tabs.tabs.categories.visible = 'no';
        }
        if( M.modFlagOn('ciniki.sapos', 0x02000000) ) {
            this._tabs.tabs.donations.visible = 'yes';
            if( sp == '' ) { sp = 'donations'; }
            ct++;
        } else {
            this._tabs.tabs.donations.visible = 'no';
        }
        if( M.modFlagOn('ciniki.sapos', 0x02) ) {
            this._tabs.tabs.expenses.visible = 'yes';
            if( sp == '' ) { sp = 'expenses'; }
            ct++;
        } else {
            this._tabs.tabs.expenses.visible = 'no';
        }
        if( M.modFlagOn('ciniki.sapos', 0x0100) ) {
            this._tabs.tabs.mileage.visible = 'yes';
            if( sp == '' ) { sp = 'mileage'; }
            ct++;
        } else {
            this._tabs.tabs.mileage.visible = 'no';
        }
        if( M.modFlagOn('ciniki.sapos', 0x010000) ) {
            this._tabs.tabs.quotes.visible = 'yes';
            if( sp == '' ) { sp = 'quotes'; }
            ct++;
        } else {
            this._tabs.tabs.quotes.visible = 'no';
        }
        if( ct > 1 ) {
            this._tabs.visible = 'yes';
        } else {
            this._tabs.visible = 'no';
        }
        this._years.selected = new Date().getFullYear();
        
        this._tabs.cb = cb;
        this.menu.menutabSwitch(sp);
    }
}
