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
        '30':'Stripe',
        '50':'Visa',
        '55':'Mastercard',
        '60':'Discover',
        '65':'Amex',
        '80':'Credit',
        '90':'Interac',
        '100':'Cash',
        '105':'Cheque',
        '110':'Email Transfer',
        '115':'Gift Certificate',
        '120':'Other',
        };

    this._tabs = {'label':'', 'visible':'no', 'selected':'invoices', 'cb':'',
        'tabs':{
            'invoices':{'label':'Invoices', 'visible':'no', 'fn':'M.ciniki_sapos_main.menu.open(M.ciniki_sapos_main._tabs.cb,"invoices");'},
            'transactions':{'label':'Transactions', 'visible':'no', 'fn':'M.ciniki_sapos_main.transactions.open(M.ciniki_sapos_main._tabs.cb);'},
            'donations':{'label':'Donations', 'visible':'no', 'fn':'M.ciniki_sapos_main.donations.open(M.ciniki_sapos_main._tabs.cb);'},
            'donationcategories':{'label':'Donations', 'visible':'no', 'fn':'M.ciniki_sapos_main.donationcategories.open(M.ciniki_sapos_main._tabs.cb);'},
            'sponsorshipcategories':{'label':'Sponsorships', 'visible':'no', 'fn':'M.ciniki_sapos_main.sponsorshipcategories.open(M.ciniki_sapos_main._tabs.cb);'},
            'categories':{'label':'Categories', 'visible':'no', 'fn':'M.ciniki_sapos_main.categories.open(M.ciniki_sapos_main._tabs.cb);'},
            'pos':{'label':'POS', 'visible':'no', 'fn':'M.ciniki_sapos_main.menu.open(M.ciniki_sapos_main._tabs.cb,"pos");'},
            'orders':{'label':'Orders', 'visible':'no', 'fn':'M.ciniki_sapos_main.menu.open(M.ciniki_sapos_main._tabs.cb,"orders");'},
            'carts':{'label':'Carts', 'visible':'no', 'fn':'M.ciniki_sapos_main.menu.open(M.ciniki_sapos_main._tabs.cb,"carts");'},
            'expenses':{'label':'Expenses', 'visible':'no', 'fn':'M.ciniki_sapos_main.expenses.open(M.ciniki_sapos_main._tabs.cb);'},
            'mileage':{'label':'Mileage', 'visible':'no', 'fn':'M.ciniki_sapos_main.mileage.open(M.ciniki_sapos_main._tabs.cb);'},
            'quotes':{'label':'Quotes', 'visible':'no', 'fn':'M.ciniki_sapos_main.quotes.open(M.ciniki_sapos_main._tabs.cb);'},
//            'repeats':{'label':'Recurring', 'visible':'no', 'fn':'M.ciniki_sapos_main.menu.open(M.ciniki_sapos_main._tabs.cb);'},
            'repeats':{'label':'Recurring', 'visible':'no', 'fn':'M.ciniki_sapos_main.menu.open(M.ciniki_sapos_main._tabs.cb,"repeats");'},
//            'repeats':{'label':'Recurring', 'visible':'no', 'fn':'M.ciniki_sapos_main.repeats.open(M.ciniki_sapos_main._tabs.cb);'},
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
        'visible':function() { var s = M.ciniki_sapos_main._tabs.selected; return ((s == 'invoices' || s == 'transactions' || s == 'expenses' || s == 'quotes' || s == 'categories' || s == 'donations' || s == 'donationcategories' || s == 'sponsorshipcategories' || s == 'pos' ) ? 'yes' : 'no');},
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
    this._rtabs = {'label':'', 'type':'paneltabs', 'selected':'monthlyinvoices', 'panel':'invoices',
        'visible':function() { return (M.ciniki_sapos_main._tabs.selected == 'repeats' ? 'yes' : 'no'); },
        'tabs':{
            'monthlyinvoices':{'label':'Monthly Invoices', 'fn':'M.ciniki_sapos_main.repeatSwitch("monthlyinvoices");'},
            'quarterlyinvoices':{'label':'Quarterly Invoices', 'fn':'M.ciniki_sapos_main.repeatSwitch("quarterlyinvoices");'},
            'yearlyinvoices':{'label':'Yearly Invoices', 'fn':'M.ciniki_sapos_main.repeatSwitch("yearlyinvoices");'},
            'monthlyexpenses':{'label':'Monthly Expenses', 'fn':'M.ciniki_sapos_main.repeatSwitch("monthlyexpenses");'},
            'quarterlyexpenses':{'label':'Quarterly Expenses', 'fn':'M.ciniki_sapos_main.repeatSwitch("quarterlyexpenses");'},
            'yearlyexpenses':{'label':'Yearly Expenses', 'fn':'M.ciniki_sapos_main.repeatSwitch("yearlyexpenses");'},
        }};
    this.repeatSwitch = function(t) {
        this._rtabs.selected = t;
        this.menu.open();
    }

    //
    // The menu panel
    //
    this.menu = new M.panel('Accounting', 'ciniki_sapos_main', 'menu', 'mc', 'full', 'sectioned', 'ciniki.sapos.main.menu');
    this.menu.data = {'invoice_type':'invoices'};
    this.menu.invoice_type = 10;
    this.menu.expense_type = 10;
    this.menu.payment_status = 0;
    this.menu.menutabs = this._tabs;
    this.menu.sections = {
//        '_quickadd':{'label':'', 'visible':'no', 'buttons':{
//            'quickadd':{'label':'Quick Invoice', 'fn':'M.startApp(\'ciniki.sapos.qi\',null,\'M.ciniki_sapos_main.menu.open();\');'},
//            }},
        'years':this._years,
        'months':this._months,
        '_rtabs':this._rtabs,
        'payment_statuses':{'label':'', 'type':'paneltabs', 'selected':'0', 
            'visible':function() { return (M.ciniki_sapos_main._tabs.selected == 'invoices' || M.ciniki_sapos_main._tabs.selected == 'pos' ? 'yes' : 'no');},
            'tabs':{
                '0':{'label':'All', 'fn':'M.ciniki_sapos_main.menu.invoices(null,null,null,null,0);'},
                '10':{'label':'Payment Required', 'fn':'M.ciniki_sapos_main.menu.invoices(null,null,null,null,10);'},
                '20':{'label':'e-transfer Required',
                    'visible':function() { return M.modFlagSet('ciniki.sapos', 0x40000000); },
                    'fn':'M.ciniki_sapos_main.menu.invoices(null,null,null,null,20);',
                    },
                '30':{'label':'Shipping Required', 
                    'visible':function() { return M.modFlagSet('ciniki.sapos', 0x10000000); },
                    'fn':'M.ciniki_sapos_main.menu.invoices(null,null,null,null,30);',
                    },
                '40':{'label':'Partial Payment', 'fn':'M.ciniki_sapos_main.menu.invoices(null,null,null,null,40);'},
                '50':{'label':'Paid', 'fn':'M.ciniki_sapos_main.menu.invoices(null,null,null,null,50);'},
                '55':{'label':'Refund Required', 'fn':'M.ciniki_sapos_main.menu.invoices(null,null,null,null,55);'},
                '60':{'label':'Refunded', 'fn':'M.ciniki_sapos_main.menu.invoices(null,null,null,null,60);'},
            }},
        'invoice_search':{'label':'', 'type':'livesearchgrid', 'livesearchcols':5, 
            'visible':function() { return M.ciniki_sapos_main._tabs.selected == 'invoices' ? 'yes' : 'no'; },
            'headerValues':['Invoice #','Date','Customer','Amount','Status'],
            'headerClasses':['', '', '', 'alignright', 'alignright'],
            'cellClasses':['', '', '', 'alignright', 'alignright'],
            'hint':'Search invoice # or customer name', 
            'noData':'No Invoices Found',
            },
        'invoices':{'label':'', 'type':'simplegrid', 'num_cols':5,
            'visible':function() { 
                if( M.ciniki_sapos_main._tabs.selected == 'invoices' ) { return 'yes'; }
                if( M.ciniki_sapos_main._tabs.selected == 'pos' ) { return 'yes'; }
                if( M.ciniki_sapos_main._tabs.selected == 'carts' ) { return 'yes'; }
                var t = M.ciniki_sapos_main._rtabs.selected;
                if( M.ciniki_sapos_main._tabs.selected == 'repeats' && 
                    (t == 'monthlyinvoices' || t == 'quarterlyinvoices' || t == 'yearlyinvoices')
                    ) {
                    return 'yes';
                }
                return 'no';
                },
            'headerValues':['Invoice #', 'Date', 'Customer', 'Amount', 'Status'],
            'headerClasses':['', '', '', 'alignright', 'alignright', 'alignright', 'alignright'],
            'cellClasses':['', '', '', 'alignright', 'alignright', 'alignright', 'alignright'],
            'sortable':'yes',
            'sortTypes':['text', 'date', 'text', 'number', 'text'],
            'noData':'No Invoices',
            },
        'expenses':{'label':'', 'type':'simplegrid', 'num_cols':5,
            'visible':function() { 
                if( M.ciniki_sapos_main._tabs.selected == 'expenses' ) {
                    return 'yes';
                }
                var t = M.ciniki_sapos_main._rtabs.selected;
                if( M.ciniki_sapos_main._tabs.selected == 'repeats' && 
                    (t == 'monthlyexpenses' || t == 'quarterlyexpenses' || t == 'yearlyexpenses')
                    ) {
                    return 'yes';
                }
                return 'no';
                },
            'headerValues':['Date', 'Name', ''],
            'cellClasses':[],
            'headerClasses':[],
            'sortable':'yes',
            'sortTypes':['date', 'alttext', 'number', 'number', 'number'],
            'noData':'No Expenses Found',
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
        if( s == 'invoices' || s == 'expenses' || s == 'items' ) { return this.data[s]; }
        return this.sections[s].list;
    };
    this.menu.noData = function(s) {
        return this.sections[s].noData;
    };
    this.menu.cellValue = function(s, i, j, d) {
        if( s == 'invoices' && this.sections[s].num_cols == 7 ) {
            switch(j) {
                case 0: return d.invoice.invoice_number;
                case 1: return d.invoice.invoice_date;
                case 2: return d.invoice.customer_display_name;
                case 3: return d.invoice.subtotal_amount_display;
                case 4: return d.invoice.taxes_amount_display;
                case 5: return d.invoice.total_amount_display;
                case 6: return d.invoice.status_text;
            }
        } else if( s == 'invoices' ) {
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
        if( s == 'expenses' ) {
            switch(j) {
                case 0: return d.invoice_date;
                case 1: return M.multiline(d.name, d.description);
            }
            if( j < this.sections[s].num_cols-1 ) {
                for(k in d.items) {
                    if( d.items[k].category_id == this.data.categories[j-2].id ) {
                        return d.items[k].amount_display;
                    }
                }
                return '';
            } else {    
                return d.total_amount_display;
            }
/*    this.expenses.cellValue = function(s, i, j, d) {
        if( j == 0 ) { return d.invoice_date; }
        if( j == 1 ) { 
            if( d.description != '' ) {
                return '<span class="maintext">' + d.name + '</span><span class="subtext">' + d.description + '</span>'; 
            } else {
                return d.name; 
            }
        }
        if( j < this.sections[s].num_cols-1 ) {
            for(k in d.items) {
                if( d.items[k].category_id == this.categories[j-2].id ) {
                    return d.items[k].amount_display;
                }
            }
            return '';
        } else {
            return d.total_amount_display;
        }
    }; */
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
        if( d == null ) {
            return '';
        }
        if( s == 'invoices' || s == 'donations' ) {
            return 'M.ciniki_sapos_main.menu.openInvoice(\'' + d.invoice.id + '\');';
//            return 'M.startApp(\'ciniki.sapos.invoice\',null,\'M.ciniki_sapos_main.menu.open();\',\'mc\',{\'invoice_id\':\'' + d.invoice.id + '\'});';
        }
        if( s == 'expenses' ) {
            return 'M.ciniki_sapos_main.expense.open(\'M.ciniki_sapos_main.menu.open();\',\'' + d.id + '\',\'\',\'\');';
        }
        return '';
    }
    this.menu.openInvoice = function(i) {
        M.startApp('ciniki.sapos.invoice',null,'M.ciniki_sapos_main.menu.open();','mc',{'invoice_id':i, 'list':this.data.invoices});
    }
    this.menu.footerValue = function(s, i, d) {
        if( s == 'invoices' && M.ciniki_sapos_main._tabs.selected == 'invoices' && this.data.totals != null ) {
            if( this.sections[s].num_cols == 7 ) {
                switch(i) {
                    case 0: return this.data.totals.num_invoices;
                    case 1: return '';
                    case 2: return '';
                    case 3: return this.data.totals.subtotal_amount;
                    case 4: return this.data.totals.taxes_amount;
                    case 5: return this.data.totals.total_amount;
                    case 6: return '';
                }
            } else {
                switch(i) {
                    case 0: return this.data.totals.num_invoices;
                    case 1: return '';
                    case 2: return '';
                    case 3: return this.data.totals.total_amount;
                    case 4: return '';
                }
            }
        } 
        if( s == 'items' && M.ciniki_sapos_main._tabs.selected == 'categories' && this.data.totals != null ) {
            switch(i) {
                case 0: return this.data.totals.num_transactions;
                case (this.sections[s].num_cols - 2): return this.data.totals.total;
            }
        }
        if( s == 'invoices' && M.ciniki_sapos_main._rtabs.selected.search(/invoices/) && this.data.totals != null && this.data.totals.total_amount != '$0.00' ) {
            if( this.sections[s].num_cols == 7 && i == 3 ) {
                return this.data.totals.subtotal_amount;
            }
            if( this.sections[s].num_cols == 7 && i == 4 ) {
                return this.data.totals.taxes_amount;
            }
            if( i == (this.sections[s].num_cols - 2) ) { return this.data.totals.total_amount;  }
            return '';
        }
        if( s == 'expenses' && M.ciniki_sapos_main._rtabs.selected.search(/expenses/) ) {
            if( i < 2 ) { return ''; }
            if( i < this.sections[s].num_cols-1 ) {
                return this.data.categories[i-2].total_amount_display;
            } else {
                return this.data.totals.total_amount_display;
            }
        }
        return null;
    }
    this.menu.footerClass = function(s, i, d) {
        if( s == 'invoices' && i > 2 ) { return 'alignright'; }
        if( s == 'expenses' && i > 2 ) { return 'alignright'; }
        if( s == 'transactions' && i > 1 ) { return 'alignright'; }
        return '';
    }
    this.menu.yearSwitch = function(y) { this.invoices(null,y); }
    this.menu.monthSwitch = function(m) { this.invoices(null,null,m); }
    this.menu.open = function(cb, type) {
        this.delButton('add');
        this.delButton('download');

        this.sections.years.visible = 'no';
        this.size = 'full';
        if( M.modOn('ciniki.taxes') ) {
            this.sections.invoices.num_cols = 7;
            this.sections.invoices.headerValues = ['Invoice #', 'Date', 'Customer', 'Amount', 'Taxes', 'Total', 'Status'];
        } else {
            this.sections.invoices.num_cols = 5;
            this.sections.invoices.headerValues = ['Invoice #', 'Date', 'Customer', 'Amount', 'Status'];
        }
        if( M.ciniki_sapos_main._tabs.selected == 'invoices' ) {
            this.invoice_type = 10;
            M.ciniki_sapos_main.menu.invoices(cb);
            this.addButton('add', 'Invoice', 'M.startApp(\'ciniki.sapos.invoice\',null,\'M.ciniki_sapos_main.menu.open();\',\'mc\',{});');
            this.addButton('download', 'Excel', 'M.ciniki_sapos_main.menu.downloadExcel();');
        }
        else if( M.ciniki_sapos_main._tabs.selected == 'carts' || M.ciniki_sapos_main._tabs.selected == 'pos' || M.ciniki_sapos_main._tabs.selected == 'orders' ) {
            switch(M.ciniki_sapos_main._tabs.selected) {
                case 'carts': 
                    this.sections.invoices.headerValues[0] = 'Cart #';
//                    this.sections.invoices.headerValues = ['Cart #', 'Date', 'Customer', 'Amount', 'Status'];
                    this.sections.invoices.noData = 'No open shopping carts';
                    M.ciniki_sapos_main.menu.invoices(cb,null,null,20, 0);
                    break;
                case 'pos': 
                    this.sections.invoices.headerValues[0] = 'POS #';
//                    this.sections.invoices.headerValues = ['POS #', 'Date', 'Customer', 'Amount', 'Status'];
                    this.sections.invoices.noData = 'No open sales';
                    M.ciniki_sapos_main.menu.invoices(cb,null,null,30, 0);
                    break;
                case 'orders': 
                    this.sections.invoices.headerValues[0] = 'Order #';
//                    this.sections.invoices.headerValues = ['Order #', 'Date', 'Customer', 'Amount', 'Status'];
                    this.sections.invoices.noData = 'No open orders';
                    M.ciniki_sapos_main.menu.invoices(cb,null,null,40, 0);
                    break;
            }
        } 
        //else if( M.ciniki_sapos_main._tabs.selected == 'monthlyinvoices' || M.ciniki_sapos_main._tabs.selected == 'quarterlyinvoices' || M.ciniki_sapos_main._tabs.selected == 'yearlyinvoices') {
        else if( M.ciniki_sapos_main._tabs.selected == 'repeats' 
            && (M.ciniki_sapos_main._rtabs.selected == 'monthlyinvoices' || M.ciniki_sapos_main._rtabs.selected == 'quarterlyinvoices' || M.ciniki_sapos_main._rtabs.selected == 'yearlyinvoices') 
            ) {
            this.addButton('add', 'Invoice', 'M.startApp(\'ciniki.sapos.invoice\',null,\'M.ciniki_sapos_main.menu.open();\',\'mc\',{});');
            switch(M.ciniki_sapos_main._rtabs.selected) {
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
        else if( M.ciniki_sapos_main._tabs.selected == 'repeats' 
            && (M.ciniki_sapos_main._rtabs.selected == 'monthlyexpenses' || M.ciniki_sapos_main._rtabs.selected == 'quarterlyexpenses' || M.ciniki_sapos_main._rtabs.selected == 'yearlyexpenses') 
            ) {
            this.addButton('add', 'Expense', 'M.ciniki_sapos_main.expense.open(\'M.ciniki_sapos_main.menu.open();\',0,\'\',\'\');');
            switch(M.ciniki_sapos_main._rtabs.selected) {
                case 'monthlyexpenses': this.expense_type = 20; break;
                case 'quarterlyexpenses': this.expense_type = 30; break;
                case 'yearlyexpenses': this.expense_type = 40; break;
            }
            M.api.getJSONCb('ciniki.sapos.expenseGrid', {'tnid':M.curTenantID, 'sort':'date', 'expense_type':this.expense_type}, function(rsp) {
                if( rsp.stat != 'ok' ) {
                    M.api.err(rsp);
                    return false;
                }
                var p = M.ciniki_sapos_main.menu;
                p.data = rsp;
                p.sections.expenses.headerValues = ['Date', 'Name'];
                p.sections.expenses.cellClasses = ['', 'multiline'];
                p.sections.expenses.headerClasses = ['', 'multiline'];
                p.sections.expenses.sortTypes = ['date', 'text'];
                p.sections.expenses.num_cols = rsp.categories.length + 3;
                for(var i in rsp.categories) {
                    p.sections.expenses.headerValues.push(rsp.categories[i].name);
                    p.sections.expenses.cellClasses.push('alignright');
                    p.sections.expenses.headerClasses.push('alignright');
                    p.sections.expenses.sortTypes.push('number');
                }
                p.sections.expenses.headerValues.push('Total');
                p.sections.expenses.cellClasses.push('alignright');
                p.sections.expenses.headerClasses.push('alignright');
                p.sections.expenses.sortTypes.push('number');
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
        if( this.payment_status == 30 ) {
            args['payment_status'] = 50;
            args['shipping_status'] = 10;
        }
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
                p.sections.years.visible = 'yes';
                if( p.sections.years.selected == '' ) {
                    p.sections.years.selected = year;
                }
                if( rsp.stats.max_invoice_date_year != null && year < rsp.stats.max_invoice_date_year ) {
                    year++;
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
        'transactions':{'label':'', 'type':'simplegrid', 'num_cols':8,
            'visible':function() { return (M.ciniki_sapos_main._tabs.selected == 'transactions' ? 'yes' : 'no');},
            'headerValues':['Type', 'Source', 'Date', 'Invoice #', 'Customer', 'Amount', 'Fees', 'Net', 'Status'],
            'headerClasses':['', '', '', '', '', 'alignright', 'alignright', 'alignright', 'alignright'],
            'cellClasses':['', '', '', '', '', 'alignright', 'alignright', 'alignright', 'alignright'],
            'sortable':'yes',
            'sortTypes':['text', 'text', 'date', 'number', 'text', 'number', 'number', 'number'],
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
                case 1: return d.source_text;
                case 2: return d.transaction_date;
                case 3: return d.invoice_number;
                case 4: return d.customer_display_name;
                case 5: return d.customer_amount_display;
                case 6: return d.transaction_fees_display;
                case 7: return d.tenant_amount_display;
                case 8: return d.status_text;
            }
        }
    }
    this.transactions.rowFn = function(s, i, d) {
        if( d == null ) {
            return '';
        }
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
                case 4: return '';
                case 5: return this.data.totals.customer_amount_display;
                case 6: return this.data.totals.transaction_fees_display;
                case 7: return this.data.totals.tenant_amount_display;
                case 8: return '';
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
    this.transactions.downloadExcel = function() {
        var args = {'tnid':M.curTenantID, 'output':'excel'};
        if( this.sections.years.selected != null ) { args.year = this.sections.years.selected; }
        if( this.sections.months.selected != null ) { args.month = this.sections.months.selected; }
//        if( this.invoice_type != null ) { args.type = this.invoice_type; }
//        if( this.payment_status != null ) { args.payment_status = this.payment_status; }
        M.api.openFile('ciniki.sapos.transactionList', args);
    }
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
                    if( rsp.stats.max_invoice_date_year != null && year < rsp.stats.max_invoice_date_year ) {
                        year++;
                    }   
                    for(var i=rsp.stats.min_invoice_date_year;i<=year;i++) {
                        p.sections.years.tabs[i] = {'label':i, 'fn':'M.ciniki_sapos_main.transactions.open(null,' + i + ',0);'};
                    }
                }
                p.refresh();
                p.show(cb);
            });
    }
    this.transactions.addButton('download', 'Excel', 'M.ciniki_sapos_main.transactions.downloadExcel();');
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
        M.confirm("Are you sure you want to remove this transaction?",null,function() {
            M.api.getJSONCb('ciniki.sapos.transactionDelete', {'tnid':M.curTenantID, 'transaction_id':tid}, function(rsp) {
                if( rsp.stat != 'ok' ) {
                    M.api.err(rsp);
                    return false;
                }
                M.ciniki_sapos_main.transaction.close();
            });
        });
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
        'invoices':{'label':'', 'type':'simplegrid', 'num_cols':7,
            'headerValues':['Invoice #', 'Date', 'Rcpt #', 'Status', 'Customer', 'Amount', 'Status'],
            'headerClasses':['', '', '', '', '', 'alignright', 'alignright'],
            'cellClasses':['', '', '', '', '', 'alignright', 'alignright'],
            'sortable':'yes',
            'sortTypes':['number', 'date', 'number', 'text', 'text', 'number', 'text', 'number'],
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
                case 2: return d.receipt_number;
                case 3: return d.donationreceipt_status_text;
                case 4: return d.customer_display_name;
                case 5: return d.donation_amount_display;
                case 6: return d.status_text;
            }
        }
    }
    this.donations.rowFn = function(s, i, d) {
        if( d == null ) {
            return '';
        }
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
                case 3: return '';
                case 4: return '';
                case 5: return this.data.totals.donation_amount;
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
                    if( rsp.stats.max_invoice_date_year != null && year < rsp.stats.max_invoice_date_year ) {
                        year++;
                    }   
                    for(var i=rsp.stats.min_invoice_date_year;i<=year;i++) {
                        p.sections.years.tabs[i] = {'label':i, 'fn':'M.ciniki_sapos_main.donations.open(null,' + i + ',0);'};
                    }
                }
                p.refresh();
                p.show(cb);
            });
    }
    this.donations.downloadExcel = function() {
        var args = {'tnid':M.curTenantID, 'output':'excel', 'sort':'invoice_date'};
        if( this.sections.years.selected != null ) { args.year = this.sections.years.selected; }
        if( this.sections.months.selected != null ) { args.month = this.sections.months.selected; }
        args.payment_status = 50;
        M.api.openFile('ciniki.sapos.donationList', args);
    }
    this.donations.addButton('download', 'Excel', 'M.ciniki_sapos_main.donations.downloadExcel();');
    this.donations.addClose('Back');

    //
    // The categories panel
    //
    this.donationcategories = new M.panel('Donation Categories', 'ciniki_sapos_main', 'donationcategories', 'mc', 'full', 'sectioned', 'ciniki.sapos.main.donationcategories');
    this.donationcategories.data = {};
    this.donationcategories.menutabs = this._tabs;
    this.donationcategories.sections = {
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
    this.donationcategories.noData = function(s) {
        return this.sections[s].noData;
    }
    this.donationcategories.headerValue = function(s, i, d) {
        if( i == 0 ) { return 'Invoice #'; }
        if( i == 1 ) { return 'Rcpt #'; }
        if( i == 2 ) { return 'Date'; }
        if( i == 3 ) { return 'Customer'; }
        if( i < this.sections[s].num_cols-1 ) {
            return this.categories[(i-4)].name;
        } else {
            return 'Total';
        }
    }
    this.donationcategories.cellValue = function(s, i, j, d) {
        if( j == 0 ) { return d.invoice_number; }
        if( j == 1 ) { return d.receipt_number; }
        if( j == 2 ) { return d.invoice_date; }
        if( j == 3 ) { return d.customer_display_name; }
        if( j < this.sections[s].num_cols-1 ) {
            if( d.categories[this.categories[(j-4)].name] != null ) {
                return d.categories[this.categories[(j-4)].name].amount_display;
            }
            return '';
        } else {
            return d.total_amount_display;
        }
    }
    this.donationcategories.cellFn = function(s, i, j, d) {
        if( j > 3 && j < this.sections[s].num_cols-1 ) {
            return 'event.stopPropagation(); M.ciniki_sapos_main.dc.open(\'M.ciniki_sapos_main.donationcategories.open();\',\'' + d.id + '\');';
        }
        return '';
    }
    this.donationcategories.rowFn = function(s, i, d) {
        if( d == null ) {
            return '';
        }
        if( s == 'invoices' ) {
            return 'M.ciniki_sapos_main.donationcategories.openInvoice(\'' + d.id + '\');';
//            return 'M.ciniki_sapos_main.dc.open(\'M.ciniki_sapos_main.donationcategories.open();\',\'' + d.id + '\');';
        }
    }
    this.donationcategories.openInvoice = function(i) {
        M.startApp('ciniki.sapos.invoice',null,'M.ciniki_sapos_main.donationcategories.open();','mc',{'invoice_id':i});
    }
    this.donationcategories.footerValue = function(s, i, j, d) {
        if( i == 0 ) { return this.data.totals.num_invoices; }
        if( i < 4 ) { return ''; }
        if( i < this.sections[s].num_cols-1 ) {
            return this.categories[(i-4)].total_amount_display;
        } else {
            return this.data.totals.total_amount;
        }
    }
    this.donationcategories.footerClass = function(s, i, d) {
        if( s == 'invoices' && i > 1 ) { return 'alignright'; }
        return '';
    }
    this.donationcategories.yearSwitch = function(y) { this.open(null,y); }
    this.donationcategories.monthSwitch = function(m) { this.open(null,null,m); }
    this.donationcategories.open = function(cb, year, month) {
        this.sections.years.panel = 'donationcategories';
        this.sections.months.panel = 'donationcategories';
        if( year != null ) { this.sections.years.selected = year; }
        if( month != null ) { this.sections.months.selected = month; }
        this.sections.years.visible = 'no';
        M.api.getJSONCb('ciniki.sapos.donationCategories', {'tnid':M.curTenantID, 'year':M.ciniki_sapos_main._years.selected, 'month':M.ciniki_sapos_main._months.selected, 'payment_status':50, 'stats':'yes'}, function(rsp) {
            if( rsp.stat != 'ok' ) {
                M.api.err(rsp);
                return false;
            }
            var p = M.ciniki_sapos_main.donationcategories;
            p.data = rsp;
            p.categories = rsp.categories;
            p.sections.invoices.headerValues = ['Invoice #', 'Rcpt #', 'Date', 'Customer'];
            p.sections.invoices.headerClasses = ['', '', '', ''];
            p.sections.invoices.cellClasses = ['', '', '', ''];
            p.sections.invoices.sortTypes = ['number', 'number', 'date', 'text'];
            p.sections.invoices.num_cols = 4;
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
                if( rsp.stats.max_invoice_date_year != null && year < rsp.stats.max_invoice_date_year ) {
                    year++;
                }   
                for(var i=rsp.stats.min_invoice_date_year;i<=year;i++) {
                    p.sections.years.tabs[i] = {'label':i, 'fn':'M.ciniki_sapos_main.yearSwitch(' + i + ');'};
                }
            }
            p.refresh();
            p.show(cb);
        });
    }
    this.donationcategories.downloadExcel = function() {
        var args = {'tnid':M.curTenantID, 'output':'excel'};
        if( this.sections.years.selected != null ) { args.year = this.sections.years.selected; }
        if( this.sections.months.selected != null ) { args.month = this.sections.months.selected; }
        args.payment_status = 50;
        M.api.openFile('ciniki.sapos.donationCategories', args);
    }
    this.donationcategories.addButton('download', 'Excel', 'M.ciniki_sapos_main.donationcategories.downloadExcel();');
    this.donationcategories.addClose('Back');

    //
    // The sponsorships categories panel
    //
    this.sponsorshipcategories = new M.panel('Sponsorship Categories', 'ciniki_sapos_main', 'sponsorshipcategories', 'mc', 'full', 'sectioned', 'ciniki.sapos.main.sponsorshipcategories');
    this.sponsorshipcategories.data = {};
    this.sponsorshipcategories.menutabs = this._tabs;
    this.sponsorshipcategories.sections = {
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
    this.sponsorshipcategories.noData = function(s) {
        return this.sections[s].noData;
    }
    this.sponsorshipcategories.headerValue = function(s, i, d) {
        if( i == 0 ) { return 'Invoice #'; }
        if( i == 1 ) { return 'Date'; }
        if( i == 2 ) { return 'Customer'; }
        if( i < this.sections[s].num_cols-1 ) {
            return this.subcategories[(i-3)].name;
        } else {
            return 'Total';
        }
    }
    this.sponsorshipcategories.cellValue = function(s, i, j, d) {
        if( j == 0 ) { return d.invoice_number; }
        if( j == 1 ) { return d.invoice_date; }
        if( j == 2 ) { return d.customer_display_name; }
        if( j < this.sections[s].num_cols-1 ) {
            if( d.subcategories[this.subcategories[(j-3)].name] != null ) {
                return d.subcategories[this.subcategories[(j-3)].name].amount_display;
            }
            return '';
        } else {
            return d.total_amount_display;
        }
    }
    this.sponsorshipcategories.rowFn = function(s, i, d) {
        if( d == null ) {
            return '';
        }
        if( s == 'invoices' ) {
            return 'M.ciniki_sapos_main.sc.open(\'M.ciniki_sapos_main.sponsorshipcategories.open();\',\'' + d.id + '\');';
        }
    }
    this.sponsorshipcategories.footerValue = function(s, i, j, d) {
        if( i == 0 ) { return this.data.totals.num_invoices; }
        if( i < 3 ) { return ''; }
        if( i < this.sections[s].num_cols-1 ) {
            return this.subcategories[(i-3)].total_amount_display;
        } else {
            return this.data.totals.total_amount;
        }
    }
    this.sponsorshipcategories.footerClass = function(s, i, d) {
        if( s == 'invoices' && i > 1 ) { return 'alignright'; }
        return '';
    }
    this.sponsorshipcategories.yearSwitch = function(y) { this.open(null,y); }
    this.sponsorshipcategories.monthSwitch = function(m) { this.open(null,null,m); }
    this.sponsorshipcategories.open = function(cb, year, month) {
        this.sections.years.panel = 'sponsorshipcategories';
        this.sections.months.panel = 'sponsorshipcategories';
        if( year != null ) { this.sections.years.selected = year; }
        if( month != null ) { this.sections.months.selected = month; }
        this.sections.years.visible = 'no';
        M.api.getJSONCb('ciniki.sapos.sponsorshipCategories', {'tnid':M.curTenantID, 'year':M.ciniki_sapos_main._years.selected, 'month':M.ciniki_sapos_main._months.selected, 'payment_status':50, 'stats':'yes'}, function(rsp) {
            if( rsp.stat != 'ok' ) {
                M.api.err(rsp);
                return false;
            }
            var p = M.ciniki_sapos_main.sponsorshipcategories;
            p.data = rsp;
            p.subcategories = rsp.subcategories;
            p.sections.invoices.headerValues = ['Invoice #', 'Date', 'Customer'];
            p.sections.invoices.headerClasses = ['', '', ''];
            p.sections.invoices.cellClasses = ['', '', ''];
            p.sections.invoices.sortTypes = ['number', 'date', 'text'];
            p.sections.invoices.num_cols = 3;
            for(var i in p.subcategories) {
                p.sections.invoices.headerValues.push(p.subcategories[i].name);
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
                if( rsp.stats.max_invoice_date_year != null && year < rsp.stats.max_invoice_date_year ) {
                    year++;
                }   
                for(var i=rsp.stats.min_invoice_date_year;i<=year;i++) {
                    p.sections.years.tabs[i] = {'label':i, 'fn':'M.ciniki_sapos_main.yearSwitch(' + i + ');'};
                }
            }
            p.refresh();
            p.show(cb);
        });
    }
    this.sponsorshipcategories.downloadExcel = function() {
        var args = {'tnid':M.curTenantID, 'output':'excel'};
        if( this.sections.years.selected != null ) { args.year = this.sections.years.selected; }
        if( this.sections.months.selected != null ) { args.month = this.sections.months.selected; }
        args.payment_status = 50;
        M.api.openFile('ciniki.sapos.sponsorshipCategories', args);
    }
    this.sponsorshipcategories.addButton('download', 'Excel', 'M.ciniki_sapos_main.sponsorshipcategories.downloadExcel();');
    this.sponsorshipcategories.addClose('Back');

    //
    // The invoice categories panel, used to manage the categories invoice items are in
    //
    this.dc = new M.panel('Invoice', 'ciniki_sapos_main', 'dc', 'mc', 'large', 'sectioned', 'ciniki.sapos.main.dc');
    this.dc.invoice_id = 0;
    this.dc.data = {};
    this.dc.sections = {
        'details':{'label':'', 'list':{
            'invoice_number':{'label':'Invoice #'},
            'po_number':{'label':'PO #'},
            'invoice_date':{'label':'Invoice Date'},
            }},
        'customer_details':{'label':'', 'type':'simplegrid', 'num_cols':2,
            'cellClasses':['label',''],
            },
        'items':{'label':'Donation Categories', 'fields':{}},
        '_buttons':{'label':'', 'buttons':{
            'save':{'label':'Save', 'fn':'M.ciniki_sapos_main.dc.save();'},
            }},
    }
    this.dc.liveSearchCb = function(s, i, v) {
        M.api.getJSONBgCb('ciniki.sapos.donationCategoriesSearch', {'tnid':M.curTenantID, 'field':i, 'start_needle':'', 'limit':15}, function(rsp) {
            M.ciniki_sapos_main.dc.liveSearchShow(s,i,M.gE(M.ciniki_sapos_main.dc.panelUID + '_' + i), rsp.categories);
        });
    }
    this.dc.liveSearchResultValue = function(s,f,i,j,d) {
        return d.name;
    }
    this.dc.liveSearchResultRowFn = function(s,f,i,j,d) {
        return 'M.ciniki_sapos_main.dc.updateField(\'' + s + '\', \'' + f + '\', \'' + escape(d.name) + '\');';
    }
    this.dc.updateField = function(s, f, v) {
        this.setFieldValue(f, unescape(v));
        this.removeLiveSearch(s, f);
    }
    this.dc.listLabel = function(s, i, d) {
        return d.label;
    }
    this.dc.listValue = function(s, i, d) {
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
    this.dc.sectionData = function(s) {
        if( s == 'details' ) { return this.sections[s].list; }
        return this.data[s];
    }
    this.dc.fieldValue = function(s, i, d) {
        return d.value;
    }
    this.dc.cellValue = function(s, i, j, d) {
        if( s == 'customer_details' ) {
            switch (j) {
                case 0: return d.detail.label;
                case 1: return (d.detail.label == 'Email'?M.linkEmail(d.detail.value):d.detail.value);
            }
        }
    }
    this.dc.open = function(cb, iid) {
        if( iid != null ) { this.invoice_id = iid; }
        M.api.getJSONCb('ciniki.sapos.invoiceGet', {'tnid':M.curTenantID, 'invoice_id':this.invoice_id}, function(rsp) {
            if( rsp.stat != 'ok' ) {
                M.api.err(rsp);
                return false;
            }
            var p = M.ciniki_sapos_main.dc;
            p.data = rsp.invoice;
            p.sections.items.fields = {};
            if( rsp.invoice.items != null ) {
                for(var i in rsp.invoice.items) {
                    if( (rsp.invoice.items[i].item.flags&0x8800) == 0 ) {
                        continue;
                    }
                    p.sections.items.fields['item_' + rsp.invoice.items[i].item.id] = {
                        'label':rsp.invoice.items[i].item.description, 
                        'type':'text', 'livesearch':'yes', 'livesearchempty':'yes',
                        'value':rsp.invoice.items[i].item.subcategory,
                        };
                }
            }
            p.refresh();
            p.show(cb);
        });
    }
    this.dc.save = function() {
        var c = this.serializeForm('no');
        if( c != '' ) {
            M.api.postJSONCb('ciniki.sapos.donationCategoriesUpdate', {'tnid':M.curTenantID, 'invoice_id':this.invoice_id}, c, function(rsp) {
                if( rsp.stat != 'ok' ) {
                    M.api.err(rsp);
                    return false;
                }
                M.ciniki_sapos_main.dc.close();
            });
        } else {
            this.close();
        }
    }
    this.dc.addClose('Back');

    //
    // The invoice categories panel, used to manage the categories invoice items are in
    //
    this.sc = new M.panel('Invoice', 'ciniki_sapos_main', 'sc', 'mc', 'large', 'sectioned', 'ciniki.sapos.main.sc');
    this.sc.invoice_id = 0;
    this.sc.data = {};
    this.sc.sections = {
        'details':{'label':'', 'list':{
            'invoice_number':{'label':'Invoice #'},
            'po_number':{'label':'PO #'},
            'invoice_date':{'label':'Invoice Date'},
            }},
        'customer_details':{'label':'', 'type':'simplegrid', 'num_cols':2,
            'cellClasses':['label',''],
            },
        'items':{'label':'Invoice Items Categories', 'fields':{}},
        '_buttons':{'label':'', 'buttons':{
            'save':{'label':'Save', 'fn':'M.ciniki_sapos_main.sc.save();'},
            }},
    }
    this.sc.liveSearchCb = function(s, i, v) {
        M.api.getJSONBgCb('ciniki.sapos.sponsorshipCategoriesSearch', {'tnid':M.curTenantID, 'field':i, 'start_needle':'', 'limit':15}, function(rsp) {
            M.ciniki_sapos_main.sc.liveSearchShow(s,i,M.gE(M.ciniki_sapos_main.sc.panelUID + '_' + i), rsp.categories);
        });
    }
    this.sc.liveSearchResultValue = function(s,f,i,j,d) {
        return d.name;
    }
    this.sc.liveSearchResultRowFn = function(s,f,i,j,d) {
        return 'M.ciniki_sapos_main.sc.updateField(\'' + s + '\', \'' + f + '\', \'' + escape(d.name) + '\');';
    }
    this.sc.updateField = function(s, f, v) {
        this.setFieldValue(f, unescape(v));
        this.removeLiveSearch(s, f);
    }
    this.sc.listLabel = function(s, i, d) {
        return d.label;
    }
    this.sc.listValue = function(s, i, d) {
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
    this.sc.sectionData = function(s) {
        if( s == 'details' ) { return this.sections[s].list; }
        return this.data[s];
    }
    this.sc.fieldValue = function(s, i, d) {
        return d.value;
    }
    this.sc.cellValue = function(s, i, j, d) {
        if( s == 'customer_details' ) {
            switch (j) {
                case 0: return d.detail.label;
                case 1: return (d.detail.label == 'Email'?M.linkEmail(d.detail.value):d.detail.value);
            }
        }
    }
    this.sc.open = function(cb, iid) {
        if( iid != null ) { this.invoice_id = iid; }
        M.api.getJSONCb('ciniki.sapos.invoiceGet', {'tnid':M.curTenantID, 'invoice_id':this.invoice_id}, function(rsp) {
            if( rsp.stat != 'ok' ) {
                M.api.err(rsp);
                return false;
            }
            var p = M.ciniki_sapos_main.sc;
            p.data = rsp.invoice;
            p.sections.items.fields = {};
            if( rsp.invoice.items != null ) {
                for(var i in rsp.invoice.items) {
                    if( rsp.invoice.items[i].item.object != 'ciniki.sponsors.package' ) {
                        continue;
                    }
                    p.sections.items.fields['item_' + rsp.invoice.items[i].item.id] = {
                        'label':rsp.invoice.items[i].item.description, 
                        'type':'text', 'livesearch':'yes', 'livesearchempty':'yes',
                        'value':rsp.invoice.items[i].item.subcategory,
                        };
                }
            }
            p.refresh();
            p.show(cb);
        });
    }
    this.sc.save = function() {
        var c = this.serializeForm('no');
        if( c != '' ) {
            M.api.postJSONCb('ciniki.sapos.sponsorshipCategoriesUpdate', {'tnid':M.curTenantID, 'invoice_id':this.invoice_id}, c, function(rsp) {
                if( rsp.stat != 'ok' ) {
                    M.api.err(rsp);
                    return false;
                }
                M.ciniki_sapos_main.sc.close();
            });
        } else {
            this.close();
        }
    }
    this.sc.addClose('Back');

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
        if( d == null ) {
            return '';
        }
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
                if( rsp.stats.max_invoice_date_year != null && year < rsp.stats.max_invoice_date_year ) {
                    year++;
                }
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
                    if( M.modFlagOn('ciniki.sapos', 0x0400) ) {
                        p.sections.items.fields['item_' + rsp.invoice.items[i].item.id] = {
                            'label':rsp.invoice.items[i].item.code + (rsp.invoice.items[i].item.code != '' ? ' - ' : '') + rsp.invoice.items[i].item.description, 
                            'type':'text', 'livesearch':'yes', 'livesearchempty':'yes',
                            'value':rsp.invoice.items[i].item.category,
                            };
                    } else {
                        p.sections.items.fields['item_' + rsp.invoice.items[i].item.id] = {
                            'label':rsp.invoice.items[i].item.description, 
                            'type':'text', 'livesearch':'yes', 'livesearchempty':'yes',
                            'value':rsp.invoice.items[i].item.category,
                            };
                    }
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
            return this.categories[i-2].name;
        } else {
            return 'Total';
        }
    };
    this.expenses.footerValue = function(s, i, d) {
        if( i < 2 ) { return ''; }
        if( i < this.sections[s].num_cols-1 ) {
            return this.categories[i-2].total_amount_display;
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
                case 0: return d.name;
                case 1: return d.invoice_date;
                case 2: return d.total_amount_display;
            }
        }
        return '';
    };
    this.expenses.liveSearchResultRowFn = function(s, f, i, j, d) {
        if( s == 'search' ) {
            return 'M.startApp(\'ciniki.sapos.expense\',null,\'M.ciniki_sapos_main.expenses.open();\',\'mc\',{\'expense_id\':\'' + d.id + '\'});';
        }
    };
    this.expenses.cellValue = function(s, i, j, d) {
        if( j == 0 ) { return d.invoice_date; }
        if( j == 1 ) { 
            if( d.description != '' ) {
                return '<span class="maintext">' + d.name + '</span><span class="subtext">' + d.description + '</span>'; 
            } else {
                return d.name; 
            }
        }
        if( j < this.sections[s].num_cols-1 ) {
            for(k in d.items) {
                if( d.items[k].category_id == this.categories[j-2].id ) {
                    return d.items[k].amount_display;
                }
            }
            return '';
        } else {
            return d.total_amount_display;
        }
    };
    this.expenses.cellSortValue = function(s, i, j, d) {
        if( j == 1 ) { return d.name + d.description; }
    }
    this.expenses.headerClass = function(s, i) {
        if( i > 1 ) { return 'alignright'; }
    }
    this.expenses.cellClass = function(s, i, j, d) {
        if( j == 1 ) { return 'multiline'; }
        if( j > 1 ) { return 'alignright'; }
    };
    this.expenses.rowFn = function(s, i, d) {
        if( d == null ) {
            return '';
        }
        if( s == 'expenses' ) {
            return 'M.ciniki_sapos_main.expense.open(\'M.ciniki_sapos_main.expenses.open();\',\'' + d.id + '\',\'\',\'\');';
            //return 'M.startApp(\'ciniki.sapos.expense\',null,\'M.ciniki_sapos_main.expenses.open();\',\'mc\',{\'expense_id\':\'' + d.id + '\'});';
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
                if( rsp.stats.max_invoice_date_year != null && year < rsp.stats.max_invoice_date_year ) {
                    year++;
                }
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
    this.expenses.addButton('add', 'Expense', 'M.ciniki_sapos_main.expense.open(\'M.ciniki_sapos_main.expenses.open();\',0,\'\',\'\');');
    this.expenses.addButton('download', 'Excel', 'M.ciniki_sapos_main.expenses.download();');
    this.expenses.addClose('Back');

    //
    // Get expense panel
    //
    this.expense = new M.panel('Expense',
        'ciniki_sapos_main', 'expense',
        'mc', 'medium', 'sectioned', 'ciniki.sapos.main.expense');
    this.expense.expense_id = 0;
    this.expense.data = {};
    this.expense.object = '';
    this.expense.object_id = '';
    this.expense.sections = {
        'details':{'label':'', 'aside':'left', 'fields':{
            'expense_type':{'label':'Type', 'type':'toggle', 
                'toggles':{'10':'Expense', '20':'Monthly', '30':'Quarterly', '40':'Yearly'},
                'visible':function() { return M.modFlagSet('ciniki.sapos', 0x1000); },
                },
            'name':{'label':'Name', 'type':'text', 'livesearch':'yes'},
            'description':{'label':'Description', 'type':'text'},
            'invoice_date':{'label':'Date', 'type':'text', 'size':'medium'},
//              'paid_date':{'label':'Paid Date', 'type':'text', 'size':'medium'},
            }},
        'items':{'label':'', 'aside':'right', 'fields':{
            }},
        '_notes':{'label':'Notes', 'aside':'left', 'fields':{
            'notes':{'label':'', 'hidelabel':'yes', 'type':'textarea', 'size':'medium'},
            }},
        '_buttons':{'label':'', 'aside':'left', 'buttons':{
            'save':{'label':'Save', 'fn':'M.ciniki_sapos_main.expense.save();'},
            'saveadd':{'label':'Save, Add Another', 'fn':'M.ciniki_sapos_main.expense.save(\'yes\');',
                'visible':function() { return M.ciniki_sapos_main.expense.expense_id == 0 ? 'yes' : 'no'; },
                },
            'delete':{'label':'Delete', 'fn':'M.ciniki_sapos_main.expense.remove(M.ciniki_sapos_main.expense.expense_id);',
                'visible':function() { return M.ciniki_sapos_main.expense.expense_id > 0 ? 'yes' : 'no'; },
                },
            }},
    };
    this.expense.fieldValue = function(s, i, d) {
        if( this.data[i] == null ) { return ''; }
        return this.data[i];
    };
    this.expense.liveSearchCb = function(s, i, v) {
        if( i == 'name' ) {
            M.api.getJSONBgCb('ciniki.sapos.expenseSearch', {'tnid':M.curTenantID,
                'items':'yes', 'sort':'reverse', 'start_needle':v, 'limit':15}, function(rsp) {
                    M.ciniki_sapos_main.expense.searchExpenseResults = rsp.expenses;
                    M.ciniki_sapos_main.expense.liveSearchShow(s,i,M.gE(M.ciniki_sapos_main.expense.panelUID+'_'+i), rsp.expenses);
                });
        }
    }
    this.expense.liveSearchResultValue = function(s,f,i,j,d) {
        if( f == 'name' ) {
            return d.name + ' [' + d.total_amount_display + '] <span class="subdue">' + d.description + '</span>';
        }
        return '';
    };
    this.expense.liveSearchResultRowFn = function(s,f,i,j,d) {
        if( f == 'name' ) {
            return 'M.ciniki_sapos_main.expense.updateExpense(\'' + s + '\',\'' + f + '\',' + i + ')';
        }
    };
    this.expense.updateExpense = function(s, fid, expense) {
        var e = M.ciniki_sapos_main.expense.searchExpenseResults[expense];
        if( e != null ) {
            this.setFieldValue('name', e.name);
            this.setFieldValue('description', e.description);
            if( e.items != null ) {
                for(i in e.items) {
                    var el = M.gE(M.ciniki_sapos_main.expense.panelUID + '_category_' + e.items[i].category_id);
                    if( el != null ) {
                        this.setFieldValue('category_' + e.items[i].category_id, e.items[i].amount_display);
                    }
                }
            }
            this.removeLiveSearch(s, fid);
        }
    };
    this.expense.fieldHistoryArgs = function(s, i) {
        if( s == 'items' ) {
            return {'method':'ciniki.sapos.history', 'args':{'tnid':M.curTenantID,
                'object':'ciniki.sapos.expense_item', 'object_id':this.expense_id, 'field':i}};
        }
        return {'method':'ciniki.sapos.history', 'args':{'tnid':M.curTenantID,
            'object':'ciniki.sapos.expense', 'object_id':this.expense_id, 'field':i}};
    };
    this.expense.open = function(cb, eid, obj, oid) {
        if( eid != null ) { this.expense_id = eid; }
        if( obj != null ) { this.object = obj; }
        if( oid != null ) { this.object_id = oid; }
        M.api.getJSONCb('ciniki.sapos.expenseGet', {'tnid':M.curTenantID, 'expense_id':this.expense_id}, function(rsp) {
            if( rsp.stat != 'ok' ) {
                M.api.err(rsp);
                return false;
            }
            var p = M.ciniki_sapos_main.expense;
            p.data = rsp.expense;
            p.sections.items.fields = {}
            for(i in rsp.categories) {
                p.sections.items.fields['category_' + rsp.categories[i].id] = {
                    'label':rsp.categories[i].name,
                    'type':'text',
                    'size':'small',
                    };
            }
            for(i in rsp.expense.items) {
                p.data['category_' + rsp.expense.items[i].category_id] = rsp.expense.items[i].amount;
            }
            p.refresh();
            p.show(cb);
        });
    }
    this.expense.save = function(add) {
        if( this.expense_id > 0 ) {
            var c = this.serializeForm('no');
            if( c != '' ) {
                M.api.postJSONCb('ciniki.sapos.expenseUpdate', {'tnid':M.curTenantID,
                    'expense_id':this.expense_id}, c, function(rsp) {
                        if( rsp.stat != 'ok' ) {
                            M.api.err(rsp);
                            return false;
                        }
                        M.ciniki_sapos_main.expense.close();
                    });
            } else {
                this.close();
            }
        } else {
            var c = this.serializeForm('yes');
            M.api.postJSONCb('ciniki.sapos.expenseAdd', 
                {'tnid':M.curTenantID, 'object':this.object, 'object_id':this.object_id}, c, function(rsp) {
                    if( rsp.stat != 'ok' ) {
                        M.api.err(rsp);
                        return false;
                    }
                    if( add == 'yes' ) { 
                        M.ciniki_sapos_main.expense.open(null,0); 
                    }
                    else { 
                        M.ciniki_sapos_main.expense.close(); 
                    }
                });
        }
    }
    this.expense.remove = function(eid) {
        if( eid <= 0 ) { return false; }
        M.confirm("Are you sure you want to remove this expense?",null,function() {
            M.api.getJSONCb('ciniki.sapos.expenseDelete', {'tnid':M.curTenantID, 'expense_id':eid}, function(rsp) {
                if( rsp.stat != 'ok' ) {
                    M.api.err(rsp);
                    return false;
                }
                M.ciniki_sapos_main.expense.close();
            });
        });
    }
    this.expense.addButton('save', 'Save', 'M.ciniki_sapos_main.expense.save();');
    this.expense.addClose('Cancel');

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
        if( d == null ) {
            return '';
        }
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
    // The repeats panel for recurring invoices and expenses
    //
/*    this.repeats = new M.panel('Recurring', 'ciniki_sapos_main', 'repeats', 'mc', 'large', 'sectioned', 'ciniki.sapos.main.repeats');
    this.repeats.data = {};
    this.repeats.menutabs = this._tabs;
    this.repeats.cols = [];
    this.repeats.sections = {
        'quarters':{'label':'', 'type':'simplegrid', 'num_cols':2,
            'cols':{},
            'noData':'No taxes found',
            },
    }
    this.repeats.sectionData = function(s) {
        return this.data[s];
    }
    this.repeats.headerValue = function(s, i, d) {
        if( i == 0 ) { return 'Quarter'; }
        if( i > 1 && i == (this.sections.quarters.num_cols - 1) ) { return 'Total'; }
        return this.sections.quarters.cols[(i-1)].name;
    }
    this.repeats.cellValue = function(s, i, j, d) {
        if( j == 0 ) { return d.start_date + ' - ' + d.end_date; }
        if( j > 1 && j == (this.sections.quarters.num_cols - 1) ) { return d.total_amount_display; }
        var c = this.sections.quarters.cols[(j-1)];
        return d[c.type][c.id].amount_display;
    }
//    this.repeats.setup = function() {
//    }
    this.repeats.open = function(cb, report) {
        var method = '';
        switch (report) {
            case 'taxes': method = 'ciniki.sapos.reportInvoicesTaxes'; break;
        }
        M.api.getJSONCb(method, {'tnid':M.curTenantID}, function(rsp) {
            if( rsp.stat != 'ok' ) {
                M.api.err(rsp);
                return false;
            }
            var p = M.ciniki_sapos_main.repeats;
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
    this.repeats.addClose('Back');
*/
    //
    // The reports panel 
    //
    // ******* NOT USED ANYMORE, Deprecated Feb 19, 2021 and HST report now in Reporting. *******
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
            M.alert('App Error');
            return false;
        } 

        //
        // Initialize for tenant
        //
        if( this.curTenantID == null || this.curTenantID != M.curTenantID ) {
            this.tenantInit();
            this.curTenantID = M.curTenantID;
        }

        this._tabs.tabs.repeats.visible = M.modFlagSet('ciniki.sapos', 0x1000);
        this._tabs.tabs.reports.visible = 'no';
        if( M.curTenant.modules['ciniki.taxes'] != null 
            && M.modSettingSet('ciniki.sapos', 'invoice-reports-taxes-ontario-hst') == 'yes' 
            ) {
            this._tabs.tabs.reports.visible = 'yes';
        }
        this.transactions.sections.transactions.num_cols = M.modFlagOn('ciniki.sapos',0x080000) ? 9 : 8;
        
        var ct = 0;
        var sp = '';
        if( M.modFlagOn('ciniki.sapos', 0x01) ) {
            this._tabs.tabs.invoices.visible = 'yes';
            this._tabs.tabs.transactions.visible = 'yes';
            if( sp == '' ) { sp = 'invoices'; }
            ct+=2;
        } else {
            this._tabs.tabs.invoices.visible = 'no';
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
        if( M.modFlagOn('ciniki.sapos', 0x0a000000) ) {
            this._tabs.tabs.donationcategories.visible = 'yes';
            this._tabs.tabs.donations.visible = 'no';
            if( sp == '' ) { sp = 'donationcategories'; }
            ct++;
        } else if( M.modFlagOn('ciniki.sapos', 0x02000000) ) {
            this._tabs.tabs.donationcategories.visible = 'no';
            this._tabs.tabs.donations.visible = 'yes';
            if( sp == '' ) { sp = 'donations'; }
            ct++;
        } else {
            this._tabs.tabs.donationcategories.visible = 'no';
            this._tabs.tabs.donations.visible = 'no';
        }
        if( M.modFlagOn('ciniki.sponsors', 0x10) ) {
            this._tabs.tabs.sponsorshipcategories.visible = 'yes';
            if( sp == '' ) { sp = 'sponsorshipcategories'; }
            ct++;
        } else {
            this._tabs.tabs.sponsorshipcategories.visible = 'no';
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
       
        if( M.modOn('ciniki.taxes') ) {
            this.menu.sections.invoices.num_cols = 7;
            this.menu.sections.invoices.headerValues = ['Invoice #', 'Date', 'Customer', 'Amount', 'Taxes', 'Total', 'Status'];
            this.menu.sections.invoices.sortTypes = ['text', 'date', 'text', 'number', 'number', 'number', 'text'];
        } else {
            this.menu.sections.invoices.num_cols = 5;
            this.menu.sections.invoices.headerValues = ['Invoice #', 'Date', 'Customer', 'Amount', 'Status'];
            this.menu.sections.invoices.sortTypes = ['text', 'date', 'text', 'number', 'text'];
        }
        this._tabs.cb = cb;

        if( args.expense_id != null && args.object != null && args.object_id != null ) {
            this.expense.open(cb,args.expense_id,args.object,args.object_id);
        }
        else if( args.expense_id != null ) {
            this.expense.open(cb,args.expense_id,'','');
        } 
        else {
            this.menu.menutabSwitch(sp);
        }
    }

    this.tenantInit = function() {
        var m = M.modSetting('ciniki.sapos', 'fiscal-year-start-month');
        if( m == '' ) {
            m = 1;
        } else {
            m = parseInt(m);
        }
        if( m < 1 || m > 12 ) {
            m = 1;
        }
        this._years.selected = new Date().getFullYear();
        if( m > 1 && (new Date().getMonth()+1) >= m ) {
            this._years.selected++;
        }
        this._months.tabs = {
            '0':{'label':'All', 'fn':'M.ciniki_sapos_main.monthSwitch(0);'},
            };
        for(var i = 1; i <= 12; i++) {
            this._months.tabs[i] = {'label':M.months[(m-1)].shortname, 'id':m, 'fn':'M.ciniki_sapos_main.monthSwitch(' + m + ');'};    
            m++;
            if( m > 12 ) {
                m = 1;
            }
        }
    }
}
