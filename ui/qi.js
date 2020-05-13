//
// This panel will create or edit an invoice
//
function ciniki_sapos_qi() {
    this.qiItems = {
//      '1':'Wash',
//      '2':'ShortCut',
//      '3':'LongCut',
//      '4':'Shave',
//      '5':'Trim',
//      '6':'BTrim',
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
    this.init = function() {
        //
        // The invoice panel
        //
        this.add = new M.panel('Invoice',
            'ciniki_sapos_qi', 'add',
            'mc', 'medium', 'sectioned', 'ciniki.sapos.qi.add');
        this.add.data = {};
        this.add.sections = {
            'details':{'label':'', 'fields':{
                'customer_id':{'label':'', 'hidelabel':'yes', 'type':'fkid', 'hint':'Customer', 'livesearch':'yes'},
                }},
            '_items':{'label':'', 'fields':{
                'items':{'label':'', 'hidelabel':'yes', 'type':'multiselect', 'none':'yes', 'joined':'no', 'options':this.qiItems},
                }},
            'transaction':{'label':'', 'fields':{
                'transaction_source':{'label':'', 'hidelabel':'yes', 'type':'toggle', 'join':'no', 'toggles':this.transactionSources},
                }},
            '_buttons':{'label':'', 'buttons':{
                'save':{'label':'Add', 'fn':'M.ciniki_sapos_qi.addInvoice();'},
                }},
            'latest':{'label':'', 'type':'simplegrid', 'num_cols':1,
                'cellClasses':['multiline'],
                },
            };
        this.add.liveSearchCb = function(s, i, v) {
            M.api.getJSONBgCb('ciniki.customers.searchQuick', {'tnid':M.curTenantID, 
                'start_needle':v, 'limit':11}, function(rsp) {
                    M.ciniki_sapos_qi.add.liveSearchShow(s,i,M.gE(M.ciniki_sapos_qi.add.panelUID + '_' + i), rsp.customers);
                });
        };
        this.add.liveSearchResultValue = function(s, f, i, j, d) {
            return d.customer.display_name;
        };
        this.add.liveSearchResultRowFn = function(s, f, i, j, d) {
            return 'M.ciniki_sapos_qi.add.updateCustomer(\'' + s + '\',\'' + escape(d.customer.display_name) + '\',\'' + d.customer.id + '\');';
        };
        this.add.updateCustomer = function(s, customer_name, customer_id) {
            M.gE(this.panelUID + '_customer_id').value = customer_id;
            M.gE(this.panelUID + '_customer_id_fkidstr').value = unescape(customer_name);
            this.removeLiveSearch(s, 'customer_id');
        }
        this.add.sectionData = function(s) {
            return this.data[s];
        };
        this.add.fieldValue = function(s, i, d) { return ''; }
        this.add.cellValue = function(s, i, j, d) {
            return '<span class="maintext">' + d.invoice.customer_display_name + '</span><span class="subtext">' + d.invoice.invoice_date + ' - ' + d.invoice.total_amount_display + '</span>';
        };
        this.add.rowFn = function(s, i, d) {
            if( d == null ) {
                return '';
            }
            return 'M.startApp(\'ciniki.sapos.invoice\',null,\'M.ciniki_sapos_qi.showAdd();\',\'mc\',{\'invoice_id\':\'' + d.invoice.id + '\'});';
        };
        this.add.addClose('Back');
    }; 

    this.start = function(cb, aP, aG) {
        args = {};
        if( aG != null ) { args = eval(aG); }
        var aC = M.createContainer(aP, 'ciniki_sapos_qi', 'yes');
        if( aC == null ) {
            alert('App Error');
            return false;
        }

        M.api.getJSONCb('ciniki.sapos.qiItemList', {'tnid':M.curTenantID}, function(rsp) {
            if( rsp.stat != 'ok' ) {
                M.api.err(rsp);
                return false;
            }
            var p = M.ciniki_sapos_qi.add;
            // M.ciniki_sapos_qi.qiItems = {};
            p.sections._items.fields.items.options = {};
            for(i in rsp.items) {
                p.sections._items.fields.items.options[rsp.items[i].item.id] = rsp.items[i].item.name;
                // M.ciniki_sapos_qi.qiItems[rsp.items.id] = rsp.items.name;
            }
            p.data.latest = rsp.invoices;
            p.refresh();
            p.show(cb);
        });
        this.showAdd(cb);
    };

    this.showAdd = function(cb) {
        M.api.getJSONCb('ciniki.sapos.latest', {'tnid':M.curTenantID, 
            'limit':'10', 'sort':'latest'}, function(rsp) {
                if( rsp.stat != 'ok' ) {
                    M.api.err(rsp);
                    return false;
                }
                var p = M.ciniki_sapos_qi.add;
                p.data.latest = rsp.invoices;
                p.refresh();
                p.show(cb);
            });
    };

    this.addInvoice = function(cb) {
        var c = this.add.serializeForm('yes');
        if( this.add.formValue('customer_id') == 0 ) {
            c += 'name=' + encodeURIComponent(M.gE(this.add.panelUID + '_customer_id_fkidstr').value) + '&';
        }
        M.api.postJSONCb('ciniki.sapos.qiAdd', {'tnid':M.curTenantID, 'limit':'10'},
            c, function(rsp) {
                if( rsp.stat != 'ok' ) {
                    M.api.err(rsp);
                    return false;
                }
                var p = M.ciniki_sapos_qi.add;
                p.data.latest = rsp.latest;
                p.refresh();
                p.show(cb);
            });
    };
}
