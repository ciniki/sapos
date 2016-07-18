//
// This panel will display the list of mileages in a grid similar to a spreadsheet
//
function ciniki_sapos_mileages() {
    this.init = function() {
        this.mileages = new M.panel('Mileages',
            'ciniki_sapos_mileages', 'mileages',
            'mc', 'full', 'sectioned', 'ciniki.sapos.mileages.mileages');
        this.mileages.year = null;
        this.mileages.month = 0;
        this.mileages.categories = {};
        this.mileages.data = {};
        this.mileages.sections = {
            'years':{'label':'', 'type':'paneltabs', 'selected':'', 'tabs':{}},
            'months':{'label':'', 'visible':'no', 'type':'paneltabs', 'selected':'0', 'tabs':{
                '0':{'label':'All', 'fn':'M.ciniki_sapos_mileages.showMileages(null,null,0);'},
                '1':{'label':'Jan', 'fn':'M.ciniki_sapos_mileages.showMileages(null,null,1);'},
                '2':{'label':'Feb', 'fn':'M.ciniki_sapos_mileages.showMileages(null,null,2);'},
                '3':{'label':'Mar', 'fn':'M.ciniki_sapos_mileages.showMileages(null,null,3);'},
                '4':{'label':'Apr', 'fn':'M.ciniki_sapos_mileages.showMileages(null,null,4);'},
                '5':{'label':'May', 'fn':'M.ciniki_sapos_mileages.showMileages(null,null,5);'},
                '6':{'label':'Jun', 'fn':'M.ciniki_sapos_mileages.showMileages(null,null,6);'},
                '7':{'label':'Jul', 'fn':'M.ciniki_sapos_mileages.showMileages(null,null,7);'},
                '8':{'label':'Aug', 'fn':'M.ciniki_sapos_mileages.showMileages(null,null,8);'},
                '9':{'label':'Sep', 'fn':'M.ciniki_sapos_mileages.showMileages(null,null,9);'},
                '10':{'label':'Oct', 'fn':'M.ciniki_sapos_mileages.showMileages(null,null,10);'},
                '11':{'label':'Nov', 'fn':'M.ciniki_sapos_mileages.showMileages(null,null,11);'},
                '12':{'label':'Dec', 'fn':'M.ciniki_sapos_mileages.showMileages(null,null,12);'},
                }},
            'mileages':{'label':'', 'type':'simplegrid', 'num_cols':4,
                'sortable':'yes',
                'headerValues':['Date', 'From/To', 'Distance', 'Amount'],
                'sortTypes':['date', 'text', 'number', 'number'],
                'noData':'No Mileages Found',
                },
            '_buttons':{'label':'', 'buttons':{
                'excel':{'label':'Download Excel', 'fn':'M.ciniki_sapos_mileages.downloadExcel();'},
                }},
        };
        this.mileages.sectionData = function(s) {
            return this.data[s];
        };
        this.mileages.footerValue = function(s, i, d) {
            if( i < 2 ) { return ''; }
            if( i == 2 ) { return this.data.totals.distance + ' ' + this.data.totals.units; }
            if( i == 3 ) { return this.data.totals.amount_display; }
        };
        this.mileages.footerClass = function(s, i, d) {
            if( i > 1 ) { return 'alignright'; }
        };
        this.mileages.noData = function(s) {
            return this.sections[s].noData;
        };
        this.mileages.cellValue = function(s, i, j, d) {
            if( j == 0 ) { return d.mileage.travel_date; }
            if( j == 1 ) { return d.mileage.start_name + ' - ' + d.mileage.end_name; }
            if( j == 2 ) { return d.mileage.total_distance + ' ' + d.mileage.units; }
            if( j == 3 ) { return d.mileage.amount_display; }
        };
        this.mileages.cellClass = function(s, i, j, d) {
            if( j > 1 ) { return 'alignright'; }
        };
        this.mileages.rowFn = function(s, i, d) {
            if( s == 'mileages' ) {
                return 'M.startApp(\'ciniki.sapos.mileage\',null,\'M.ciniki_sapos_mileages.showMileages();\',\'mc\',{\'mileage_id\':\'' + d.mileage.id + '\'});';
            }
        };
        this.mileages.addButton('add', 'Mileage', 'M.startApp(\'ciniki.sapos.mileage\',null,\'M.ciniki_sapos_mileages.showMileages();\',\'mc\',{});');
        this.mileages.addClose('Back');
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
        var appContainer = M.createContainer(appPrefix, 'ciniki_sapos_mileages', 'yes');
        if( appContainer == null ) {
            alert('App Error');
            return false;
        } 

        M.api.getJSONCb('ciniki.sapos.mileageStats', {'business_id':M.curBusinessID}, function(rsp) {
            if( rsp.stat != 'ok' ) {
                M.api.err(rsp);
                return false;
            }
            var p = M.ciniki_sapos_mileages.mileages;
            if( rsp.stats.min_travel_date_year != null ) {
                var year = new Date().getFullYear();
                p.sections.years.tabs = {};
                for(var i=rsp.stats.min_travel_date_year;i<=year;i++) {
                    p.sections.years.tabs[i] = {'label':i, 'fn':'M.ciniki_sapos_mileages.showMileages(null,' + i + ',null);'};
                }
            }
            var dt = new Date();
            M.ciniki_sapos_mileages.showMileages(cb, dt.getFullYear(), 0);
        });
    };

    this.showMileages = function(cb, year, month) {
        if( year != null ) {
            this.mileages.year = year;
            this.mileages.sections.years.selected = year;
        }
        if( month != null ) {
            this.mileages.month = month;
            this.mileages.sections.months.selected = month;
        }
        this.mileages.sections.months.visible = (this.mileages.month>0)?'yes':'yes';
        M.api.getJSONCb('ciniki.sapos.mileageList', {'business_id':M.curBusinessID,
            'year':this.mileages.year, 'month':this.mileages.month}, function(rsp) {
                if( rsp.stat != 'ok' ) {
                    M.api.err(rsp);
                    return false;
                }
                var p = M.ciniki_sapos_mileages.mileages;
                p.data.mileages = rsp.mileages;
                p.data.totals = rsp.totals;
                p.refresh();
                p.show(cb);
            });
    };

    this.downloadExcel = function() {
        var args = {'business_id':M.curBusinessID, 'output':'excel'};
        if( this.mileages.year != null ) { args.year = this.mileages.year; }
        if( this.mileages.month != null ) { args.month = this.mileages.month; }
//      window.open(M.api.getUploadURL('ciniki.sapos.mileageList', args));
        M.api.openFile('ciniki.sapos.mileageList', args);
    };
}
