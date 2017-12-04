//
// The app to add/edit sapos images
//
function ciniki_sapos_expenseimages() {
    this.init = function() {
        //
        // The panel to display the edit form
        //
        this.edit = new M.panel('Edit Image',
            'ciniki_sapos_expenseimages', 'edit',
            'mc', 'medium', 'sectioned', 'ciniki.sapos.images.edit');
        this.edit.default_data = {};
        this.edit.data = {};
        this.edit.expense_id = 0;
        this.edit.expense_image_id = 0;
        this.edit.sections = {
            '_image':{'label':'Photo', 'type':'imageform', 'fields':{
                'image_id':{'label':'', 'type':'image_id', 'hidelabel':'yes', 'controls':'all', 'history':'no'},
            }},
            '_save':{'label':'', 'buttons':{
                'save':{'label':'Save', 'fn':'M.ciniki_sapos_expenseimages.saveImage();'},
                'delete':{'label':'Delete', 'fn':'M.ciniki_sapos_expenseimages.deleteImage();'},
            }},
        };
        this.edit.fieldValue = function(s, i, d) { 
            if( this.data[i] != null ) {
                return this.data[i]; 
            } 
            return ''; 
        };
        this.edit.fieldHistoryArgs = function(s, i) {
            return {'method':'ciniki.sapos.imageHistory',
                'args':{'tnid':M.curTenantID, 
                'expense_image_id':M.ciniki_sapos_expenseimages.edit.expense_image_id, 'field':i}};
        };
        this.edit.addDropImage = function(iid) {
            M.ciniki_sapos_expenseimages.edit.setFieldValue('image_id', iid, null, null);
            return true;
        };
        this.edit.addButton('save', 'Save', 'M.ciniki_sapos_expenseimages.saveImage();');
        this.edit.addClose('Cancel');
    };

    this.start = function(cb, appPrefix, aG) {
        args = {};
        if( aG != null ) {
            args = eval(aG);
        }

        //
        // Create container
        //
        var appContainer = M.createContainer(appPrefix, 'ciniki_sapos_expenseimages', 'yes');
        if( appContainer == null ) {
            alert('App Error');
            return false;
        }

        if( args.add != null && args.add == 'yes' ) {
            this.showEdit(cb, 0, args.expense_id);
        } else if( args.expense_image_id != null && args.expense_image_id > 0 ) {
            this.showEdit(cb, args.expense_image_id);
        }
        return false;
    }

    this.showEdit = function(cb, iid, eid) {
        if( iid != null ) {
            this.edit.expense_image_id = iid;
        }
        if( eid != null ) {
            this.edit.expense_id = eid;
        }
        if( this.edit.expense_image_id > 0 ) {
            var rsp = M.api.getJSONCb('ciniki.sapos.expenseImageGet', 
                {'tnid':M.curTenantID, 'expense_image_id':this.edit.expense_image_id}, function(rsp) {
                    if( rsp.stat != 'ok' ) {
                        M.api.err(rsp);
                        return false;
                    }
                    M.ciniki_sapos_expenseimages.edit.data = rsp.image;
                    M.ciniki_sapos_expenseimages.edit.refresh();
                    M.ciniki_sapos_expenseimages.edit.show(cb);
                });
        } else {
            this.edit.reset();
            this.edit.data = {};
            this.edit.refresh();
            this.edit.show(cb);
        }
    };

    this.saveImage = function() {
        if( this.edit.expense_image_id > 0 ) {
            var c = this.edit.serializeFormData('no');
            if( c != '' ) {
                var rsp = M.api.postJSONFormData('ciniki.sapos.expenseImageUpdate', 
                    {'tnid':M.curTenantID, 
                    'expense_image_id':this.edit.expense_image_id}, c,
                        function(rsp) {
                            if( rsp.stat != 'ok' ) {
                                M.api.err(rsp);
                                return false;
                            } else {
                                M.ciniki_sapos_expenseimages.edit.close();
                            }
                        });
            } else {
                this.edit.close();
            }
        } else {
            var c = this.edit.serializeFormData('yes');
            var rsp = M.api.postJSONFormData('ciniki.sapos.expenseImageAdd', 
                {'tnid':M.curTenantID, 'expense_id':this.edit.expense_id}, c,
                    function(rsp) {
                        if( rsp.stat != 'ok' ) {
                            M.api.err(rsp);
                            return false;
                        } else {
                            M.ciniki_sapos_expenseimages.edit.close();
                        }
                    });
        }
    };

    this.deleteImage = function() {
        if( confirm('Are you sure you want to delete this image?') ) {
            var rsp = M.api.getJSONCb('ciniki.sapos.expenseImageDelete', {'tnid':M.curTenantID, 
                'expense_image_id':this.edit.expense_image_id}, function(rsp) {
                    if( rsp.stat != 'ok' ) {
                        M.api.err(rsp);
                        return false;
                    }
                    M.ciniki_sapos_expenseimages.edit.close();
                });
        }
    };
}
