function ciniki_sapos_carts() {
    this.init = function() {
        //
        // The panel to display the list of invoices 
        //
        this.list = new M.panel('Carts',
            'ciniki_sapos_carts', 'list',
            'mc', 'medium', 'sectioned', 'ciniki.sapos.carts.list');
        this.list.data = {};
        this.list.sections = {
            'invoices':{'label':'', 'type':'simplegrid', 'num_cols':4,
                'sortable':'yes',
                'headerValues':['Invoice #', 'Date', 'Customer', 'Status'],
                'sortTypes':['number', 'date', 'text', 'text'],
                'noData':'Nothing to be shipped',
                },
        };
        this.list.sectionData = function(s) {
            return this.data[s];
        };
        this.list.noData = function(s) {
            return this.sections[s].noData;
        };
        this.list.cellValue = function(s, i, j, d) {
            if( s == 'invoices' ) {
                switch(j) {
                    case 0: return d.invoice.invoice_number;
                    case 1: return d.invoice.invoice_date;
                    case 2: return d.invoice.customer_display_name;
                    case 3: return d.invoice.status_text;
                }
            }
        };
        this.list.rowFn = function(s, i, d) {
            if( d == null ) {
                return '';
            }
            if( s == 'invoices' ) {
                return 'M.startApp(\'ciniki.sapos.invoice\',null,\'M.ciniki_sapos_carts.showList();\',\'mc\',{\'invoice_id\':\'' + d.invoice.id + '\'});';
            }
        };
        this.list.addClose('Back');
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
        var appContainer = M.createContainer(appPrefix, 'ciniki_sapos_carts', 'yes');
        if( appContainer == null ) {
            M.alert('App Error');
            return false;
        } 

        if( args.list != null ) {
            this.showList(cb, args.list);
        }
    };

    this.showList = function(cb, list) {
        if( list != null ) { this.list._list = list; }
        if( this.list._list == 'opencarts' ) {
            M.api.getJSONCb('ciniki.sapos.invoiceList', {'tnid':M.curTenantID,
                'status':'10', 'type':'20', 'sort':'invoice_date_desc'}, function(rsp) {
                    if( rsp.stat != 'ok' ) {
                        M.api.err(rsp);
                        return false;
                    }
                    var p = M.ciniki_sapos_carts.list;
                    p.data.invoices = rsp.invoices;
                    p.refresh();
                    p.show(cb);
                });
        }
    };
}
