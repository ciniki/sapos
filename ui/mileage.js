//
// This panel will create or edit an mileage
//
function ciniki_sapos_mileage() {
    this.mileageFlags = {
        '1':{'name':'Round Trip'},
        };
    this.init = function() {
        //
        // The view mileage panel
        //
        this.mileage = new M.panel('Mileage',
            'ciniki_sapos_mileage', 'mileage',
            'mc', 'medium', 'sectioned', 'ciniki.sapos.mileage.mileage');
        this.mileage.mileage_id = 0;
        this.mileage.sections = {
            'details':{'label':'', 'list':{
                'travel_date':{'label':'Date'},
                'start_name':{'label':'Start'},
//              'start_address':{'label':'Address'},
                'end_name':{'label':'End'},
//              'end_address':{'label':'Address'},
                'flags_text':{'label':'Type'},
                'total_distance':{'label':'Distance'},
                'rate_display':{'label':'Rate'},
                'amount_display':{'label':'Amount'},
                }},
            'notes':{'label':'Notes', 'visible':'no', 'type':'htmlcontent'},
            '_buttons':{'label':'', 'buttons':{
                'edit':{'label':'Edit', 'fn':'M.ciniki_sapos_mileage.showEdit(\'M.ciniki_sapos_mileage.showMileage();\',M.ciniki_sapos_mileage.mileage.mileage_id);'},
                }},
        };
        this.mileage.sectionData = function(s) {
            if( s == 'details' ) { return this.sections[s].list; }
            return this.data[s];
        };
        this.mileage.listLabel = function(s, i, d) { return d.label; }
        this.mileage.listValue = function(s, i, d) { 
            if( i == 'start_name' ) { return this.data.start_name + ', ' + this.data.start_address; }
            if( i == 'end_name' ) { return this.data.end_name + ', ' + this.data.end_address; }
            if( i == 'total_distance' ) { return this.data.total_distance + ' ' + this.data.units; }
            return this.data[i]; 
        }
        this.mileage.fieldValue = function(s, i, d) {
            if( i == 'notes' ) {
                return this.data[i].replace(/\n/g, '<br/>');
            }
        };
        this.mileage.addButton('edit', 'Edit', 'M.ciniki_sapos_mileage.showEdit(\'M.ciniki_sapos_mileage.showMileage();\',M.ciniki_sapos_mileage.mileage.mileage_id);');
        this.mileage.addClose('Back');
            
        //
        // The edit mileage panel
        //
        this.edit = new M.panel('Mileage',
            'ciniki_sapos_mileage', 'edit',
            'mc', 'medium', 'sectioned', 'ciniki.sapos.mileage.edit');
        this.edit.mileage_id = 0;
        this.edit.data = {};
        this.edit.sections = {
            'details':{'label':'', 'fields':{
                'travel_date':{'label':'Date', 'type':'date', 'autofocus':'no'},
                'start_name':{'label':'Start', 'type':'text', 'autofocus':'no', 'livesearch':'yes'},
                'start_address':{'label':'Address', 'type':'text', 'livesearch':'yes'},
                'end_name':{'label':'End', 'type':'text', 'livesearch':'yes'},
                'end_address':{'label':'Address', 'type':'text', 'livesearch':'yes'},
                'flags':{'label':'Options', 'type':'flags', 'flags':this.mileageFlags},
                'distance':{'label':'Distance', 'type':'text', 'size':'small'},
                }},
            '_notes':{'label':'Notes', 'fields':{
                'notes':{'label':'', 'hidelabel':'yes', 'type':'textarea', 'size':'small'},
                }},
            '_buttons':{'label':'', 'buttons':{
                'save':{'label':'Save', 'fn':'M.ciniki_sapos_mileage.saveMileage();'},
                'saveadd':{'label':'Save, Add Another', 'fn':'M.ciniki_sapos_mileage.saveMileage(\'yes\');'},
                'delete':{'label':'Delete', 'fn':'M.ciniki_sapos_mileage.deleteMileage(M.ciniki_sapos_mileage.edit.mileage_id);'},
                }},
        };
        this.edit.fieldValue = function(s, i, d) {
            if( this.data[i] == null ) { return ''; }
            return this.data[i];
        };
        this.edit.liveSearchCb = function(s, i, v) {
            if( i == 'start_name' || i == 'end_name' || i == 'start_address' || i == 'end_address' ) {
                M.api.getJSONBgCb('ciniki.sapos.mileageSearchName', {'tnid':M.curTenantID,
                    'start_needle':v, 'limit':15}, function(rsp) {
                        M.ciniki_sapos_mileage.edit.searchMileageResults = rsp.mileages;
                        M.ciniki_sapos_mileage.edit.liveSearchShow(s,i,M.gE(M.ciniki_sapos_mileage.edit.panelUID+'_'+i), rsp.mileages);
                    });
            }
        }
        this.edit.liveSearchResultValue = function(s,f,i,j,d) {
            if( d.mileage != null ) {
                return d.mileage.start_name + ' - ' + d.mileage.end_name + ' <span class="subdue">(' + d.mileage.total_distance + ' ' + d.mileage.round_trip + ')'  + '</span>';
            }
            return '';
        };
        this.edit.liveSearchResultRowFn = function(s,f,i,j,d) {
            if( d.mileage != null ) {
                return 'M.ciniki_sapos_mileage.edit.updateMileage(\'' + s + '\',\'' + f + '\',' + i + ')';
            }
        };
        this.edit.updateMileage = function(s, fid, mileage) {
            var e = M.ciniki_sapos_mileage.edit.searchMileageResults[mileage];
            if( e != null && e.mileage != null ) {
                this.setFieldValue('start_name', e.mileage.start_name);
                this.setFieldValue('start_address', e.mileage.start_address);
                this.setFieldValue('end_name', e.mileage.end_name);
                this.setFieldValue('end_address', e.mileage.end_address);
                this.setFieldValue('distance', e.mileage.distance);
                this.setFieldValue('flags', e.mileage.flags);
                this.removeLiveSearch(s, fid);
            }
        };
        this.edit.fieldHistoryArgs = function(s, i) {
            return {'method':'ciniki.sapos.history', 'args':{'tnid':M.curTenantID,
                'object':'ciniki.sapos.mileage', 'object_id':this.mileage_id, 'field':i}};
        };
        this.edit.addButton('save', 'Save', 'M.ciniki_sapos_mileage.saveMileage();');
        this.edit.addClose('Cancel');
    }; 

    this.start = function(cb, aP, aG) {
        var args = {};
        if( aG != null ) { args = eval(aG); }
        var aC = M.createContainer(aP, 'ciniki_sapos_mileage', 'yes');
        if( aC == null ) {
            M.alert('App Error');
            return false;
        }

        if( args.mileage_id != null && args.mileage_id > 0 ) {
            this.showMileage(cb, args.mileage_id);
        } else {
            this.showEdit(cb, 0);
        }
    };

    this.showMileage = function(cb, eid) {
        if( eid != null ) { this.mileage.mileage_id = eid; }
        if( this.mileage.mileage_id > 0 ) {
            M.api.getJSONCb('ciniki.sapos.mileageGet', {'tnid':M.curTenantID,
                'mileage_id':this.mileage.mileage_id}, function(rsp) {
                    if( rsp.stat != 'ok' ) {
                        M.api.err(rsp);
                        return false;
                    }
                    var p = M.ciniki_sapos_mileage.mileage;
                    p.data = rsp.mileage;
                    p.sections.notes.visible=((rsp.mileage.notes!='')?'yes':'no');
                    p.refresh();
                    p.show(cb);
                });
        }
    };

    this.showEdit = function(cb, eid, date) {
        if( eid != null ) { this.edit.mileage_id = eid; }
        if( this.edit.mileage_id > 0 ) {
            this.edit.sections.details.fields.start_name.autofocus = 'no';
            this.edit.sections._buttons.buttons.delete.visible = 'yes';
            this.edit.sections._buttons.buttons.saveadd.visible = 'no';
            M.api.getJSONCb('ciniki.sapos.mileageGet', {'tnid':M.curTenantID,
                'mileage_id':this.edit.mileage_id}, function(rsp) {
                    if( rsp.stat != 'ok' ) {
                        M.api.err(rsp);
                        return false;
                    }
                    var p = M.ciniki_sapos_mileage.edit;
                    p.data = rsp.mileage;
                    p.refresh();
                    p.show(cb);
                });
        } else {
            var p = M.ciniki_sapos_mileage.edit;
            this.edit.sections.details.fields.start_name.autofocus = 'yes';
            this.edit.sections._buttons.buttons.saveadd.visible = 'yes';
            this.edit.sections._buttons.buttons.delete.visible = 'no';
            p.reset();
            p.data = {};
            if( date == null || date == '' ) {
                var dt = new Date();
                p.data.travel_date = M.dateFormat(dt);
            } else {
                p.data.travel_date = date;
            }
            p.sections._buttons.buttons.delete.visible = 'no';
            p.refresh();
            p.show(cb);
        }
    };

    this.saveMileage = function(add) {
        if( this.edit.mileage_id > 0 ) {
            var c = this.edit.serializeForm('no');
            if( c != '' ) {
                M.api.postJSONCb('ciniki.sapos.mileageUpdate', {'tnid':M.curTenantID,
                    'mileage_id':this.edit.mileage_id}, c, function(rsp) {
                        if( rsp.stat != 'ok' ) {
                            M.api.err(rsp);
                            return false;
                        }
                        M.ciniki_sapos_mileage.edit.close();
                    });
            } else {
                this.edit.close();
            }
        } else {
            var c = this.edit.serializeForm('yes');
            M.api.postJSONCb('ciniki.sapos.mileageAdd', 
                {'tnid':M.curTenantID}, c, function(rsp) {
                    if( rsp.stat != 'ok' ) {
                        M.api.err(rsp);
                        return false;
                    }
                    if( add == 'yes' ) { M.ciniki_sapos_mileage.showEdit(null,0); }
                    else { M.ciniki_sapos_mileage.edit.close(); }
                });
        }
    };

    this.deleteMileage = function(mid) {
        if( mid <= 0 ) { return false; }
        M.confirm("Are you sure you want to remove this mileage?",null,function() {
            M.api.getJSONCb('ciniki.sapos.mileageDelete', {'tnid':M.curTenantID, 'mileage_id':mid}, function(rsp) {
                if( rsp.stat != 'ok' ) {
                    M.api.err(rsp);
                    return false;
                }
                M.ciniki_sapos_mileage.mileage.close();
            });
        });
    };
}
