//
// This panel will display the list of expenses in a grid similar to a spreadsheet
//
function ciniki_sapos_main() {

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

        this.expenses.categories = {};
        M.api.getJSONCb('ciniki.sapos.expenseStats', {'tnid':M.curTenantID}, function(rsp) {
            if( rsp.stat != 'ok' ) {
                M.api.err(rsp);
                return false;
            }
            var p = M.ciniki_sapos_main.expenses;
            if( rsp.stats.min_invoice_date_year != null ) {
                var year = new Date().getFullYear();
                p.sections.years.tabs = {};
                for(var i=rsp.stats.min_invoice_date_year;i<=year;i++) {
                    p.sections.years.tabs[i] = {'label':i, 'fn':'M.ciniki_sapos_main.expenses.open(null,' + i + ',null);'};
                }
            }
            var dt = new Date();
            M.ciniki_sapos_main.expenses.open(cb, dt.getFullYear(), 0);
        });
    };

}
