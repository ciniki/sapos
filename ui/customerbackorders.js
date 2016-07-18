//
function ciniki_sapos_customerbackorders() {
    //
    // Panels
    //
    this.dayschedule = null;
    this.add = null;

    this.cb = null;
    this.toggleOptions = {'off':'Off', 'on':'On'};

    this.init = function() {
        //
        // The panel to display the Calendars, which include any business appointments
        //
        this.items = new M.panel('Customer Backordered Items',
            'ciniki_sapos_customerbackorders', 'items',
            'mc', 'fullwidth', 'sectioned', 'ciniki.sapos.customerbackorders.items');
        this.items.data = {};
        this.items.appointments = null;
        var dt = new Date();
//      this.items.date = dt.getFullYear() + '-' + (dt.getMonth()+1) + '-' + dt.getDate();
//      this.items.datePickerValue = function(s, d) { return this.date; }
        this.items.sections = {
//          'datepicker':{'label':'', 'fields':{
//              'start_date':{'label':'Start', 'type':'date', 
//                  'fn':'M.ciniki_sapos_customerbackorders.shipments.updateStartDate'},
//              'end_date':{'label':'End', 'type':'date',
//                  'fn':'M.ciniki_sapos_customerbackorders.shipments.updateEndDate'},
//              }},
//          '_buttons':{'label':'', 'buttons':{
//              'update':{'label':'Update', 'fn':'M.ciniki_sapos_customerbackorders.showItems();'},
//              'downloadtab':{'label':'Download Tab Delimited', 'fn':'M.ciniki_sapos_customerbackorders.showItems(null,null,\'tab\');'},
//              'downloadexcel':{'label':'Download Excel', 'fn':'M.ciniki_sapos_customerbackorders.showItems(null,null,\'excel\');'},
//              }},
            'items':{'label':'Backordered Items', 'type':'simplegrid', 'num_cols':18,
                'headerValues':['INV #', 'PO #', 'Order Date', 'Invoice Status', 
                    'ID', 'Customer', 'Reward', 'Rep', 
                    'Code', 'Description', 'Notes', 'Ordered', 'B/O', 'Shipped',
                    'Price Code', 'Unit Amount', 'Total', 'Tax Code'],
                'sortable':'yes',
                'sortTypes':['number','text','date','text','text','text','number','text','text','text','text','number','number','number','text','altnumber','altnumber','text'],
                'noData':'No shipments',
                },
            };
//      this.items.updateStartDate = function(field, date) {
//          this.setFromCalendar(field, date);
//          M.ciniki_sapos_customerbackorders.showItems();
//      };
//      this.items.updateEndDate = function(field, date) {
//          this.setFromCalendar(field, date);
//          M.ciniki_sapos_customerbackorders.showItems();
//      };
//      this.items.scheduleDate = function(s, d) {
//          return this.date;
//      };
        this.items.sectionData = function(s) { return this.data[s]; }
        this.items.fieldValue = function(s, i, d) { return this.data[i]; }
        this.items.cellSortValue = function(s, i, j, d) {
            if( s == 'items' ) {
                switch(j) {
                    case 0: return d.item.invoice_number;
                    case 1: return d.item.po_number;
                    case 2: return d.item.invoice_date;
                    case 3: return d.item.status_text;
                    case 4: return d.item.customer_eid;
                    case 5: return d.item.customer_display_name;
                    case 6: return d.item.reward_level;
                    case 7: return d.item.salesrep_display_name;
                    case 8: return d.item.code;
                    case 9: return d.item.description;
                    case 10: return d.item.notes;
                    case 11: return d.item.ordered_quantity;
                    case 12: return (d.item.ordered_quantity-d.item.shipped_quantity);
                    case 13: return d.item.shipped_quantity;
                    case 14: return d.item.pricepoint_code;
                    case 15: return d.item.unit_amount;
                    case 16: return d.item.total_amount;
                    case 17: return d.item.tax_location_code;
                }
            }
        }
        this.items.cellValue = function(s, i, j, d) {
            switch(j) {
                case 0: return d.item.invoice_number;
                case 1: return d.item.po_number;
                case 2: return d.item.invoice_date;
                case 3: return d.item.status_text;
                case 4: return d.item.customer_eid;
                case 5: return d.item.customer_display_name;
                case 6: return d.item.reward_level;
                case 7: return d.item.salesrep_display_name;
                case 8: return d.item.code;
                case 9: return d.item.description;
                case 10: return d.item.notes;
                case 11: return d.item.ordered_quantity;
                case 12: return (d.item.ordered_quantity-d.item.shipped_quantity);
                case 13: return d.item.shipped_quantity;
                case 14: return d.item.pricepoint_code;
                case 15: return d.item.unit_amount_display;
                case 16: return d.item.total_amount_display;
                case 17: return d.item.tax_location_code;
            }
        };
        this.items.rowFn = function(s, i, d) {
            return 'M.startApp(\'ciniki.sapos.invoice\',null,\'M.ciniki_sapos_customerbackorders.showItems();\',\'mc\',{\'invoice_id\':\'' + d.item.invoice_id + '\'});';
        };
        this.items.addClose('Back');
        this.items.addButton('download', 'Excel', 'M.ciniki_sapos_customerbackorders.showItems(null,\'excel\');');
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
        var appContainer = M.createContainer(appPrefix, 'ciniki_sapos_customerbackorders', 'yes');
        if( appContainer == null ) {
            alert('App Error');
            return false;
        } 

//      this.cb = cb;
//      if( args.date != null ) {
            this.showItems(cb);
//      } else {
//          var dt = new Date();
//          this.showItems(cb);
//      }
    }

    this.showItems = function(cb, format) {
//      if( sd != null ) { 
//          this.shipments.data.start_date = sd;
//          this.shipments.data.end_date = '';
//      } else {
//          this.shipments.data.start_date = this.shipments.formFieldValue(this.shipments.sections.datepicker.fields.start_date, 'start_date');
//          this.shipments.data.end_date = this.shipments.formFieldValue(this.shipments.sections.datepicker.fields.end_date, 'end_date');
//      }
        var args = {'business_id':M.curBusinessID, 
//          'start_date':this.shipments.data.start_date,
//          'end_date':this.shipments.data.end_date,
            };
        if( format != null ) {
            args['output'] = format;
//          window.open(M.api.getUploadURL('ciniki.sapos.reportMWExport', args));
            M.api.openFile('ciniki.sapos.backorderedCustomerItems', args);
            delete(args['output']);
        } else {
            M.api.getJSONCb('ciniki.sapos.backorderedCustomerItems', args, function(rsp) {
                if( rsp.stat != 'ok' ) {
                    M.api.err(rsp);
                    return false;
                }
                var p = M.ciniki_sapos_customerbackorders.items;
                p.data.items = rsp.items;
                p.refresh();
                p.show(cb);
            });
        }
    };
}
