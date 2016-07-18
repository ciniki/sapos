//
function ciniki_sapos_backordereditems() {
    //
    // Panels
    //
    this.add = null;

    this.cb = null;
    this.toggleOptions = {'off':'Off', 'on':'On'};

    this.init = function() {
        //
        // The panel to display the Calendars, which include any business appointments
        //
        this.items = new M.panel('Export',
            'ciniki_sapos_backordereditems', 'items',
            'mc', 'medium', 'sectioned', 'ciniki.sapos.backordereditems.items');
        this.items.data = {};
        this.items.appointments = null;
        this.items.sections = {
            '_buttons':{'label':'', 'buttons':{
                'downloadexcel':{'label':'Download Excel', 'fn':'M.ciniki_sapos_backordereditems.showBackorders(null,\'excel\');'},
                }},
            'items':{'label':'Backordered Items', 'type':'simplegrid', 'num_cols':4,
                'cellClasses':['', 'alignright', 'alignright', 'alignright'],
                'headerValues':['Code', 'Inventory', 'Reserved', 'Backordered'],
                'headerClasses':['', 'alignright', 'alignright', 'alignright'],
                'sortable':'yes',
                'sortTypes':['text', 'number', 'number', 'number'],
                'noData':'No backordered items',
                },
            };
        this.items.sectionData = function(s) { return this.data[s]; }
        this.items.fieldValue = function(s, i, d) { return this.data[i]; }
        this.items.cellValue = function(s, i, j, d) {
            switch(j) {
                case 0: return d.item.code;
                case 1: return d.item.inventory_current_num;
                case 2: return d.item.reserved_quantity;
                case 3: return d.item.backordered_quantity;
            }
        };
//      this.items.rowFn = function(s, i, d) {
//          return 'M.startApp(\'ciniki.sapos.invoice\',null,\'M.ciniki_sapos_backordereditems.showBackorders();\',\'mc\',{\'invoice_id\':\'' + d.item.invoice_id + '\'});';
//      };
        this.items.addClose('Back');
    }

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
        var appContainer = M.createContainer(appPrefix, 'ciniki_sapos_backordereditems', 'yes');
        if( appContainer == null ) {
            alert('App Error');
            return false;
        } 

        this.cb = cb;
        this.showBackorders(cb);
    }

    this.showBackorders = function(cb, format) {
        var args = {'business_id':M.curBusinessID};
        if( format != null ) {
            args['output'] = format;
            M.api.openFile('ciniki.sapos.backorderedItems', args);
            delete(args['output']);
        } else {
            M.api.getJSONCb('ciniki.sapos.backorderedItems', args, function(rsp) {
                if( rsp.stat != 'ok' ) {
                    M.api.err(rsp);
                    return false;
                }
                var p = M.ciniki_sapos_backordereditems.items;
                p.data.items = rsp.items;
                p.refresh();
                p.show(cb);
            });
        }
    };
}
