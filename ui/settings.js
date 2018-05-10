//
function ciniki_sapos_settings() {
    this.toggleOptions = {'no':'Hide', 'yes':'Display'};
    this.yesNoOptions = {'no':'No', 'yes':'Yes'};
    this.viewEditOptions = {'view':'View', 'edit':'Edit'};
    this.positionOptions = {'left':'Left', 'center':'Center', 'right':'Right', 'off':'Off'};
    this.weightUnits = {
        '10':'lb',
        '20':'kg',
        };

    //
    // The menu panel
    //
    this.menu = new M.panel('Settings', 'ciniki_sapos_settings', 'menu', 'mc', 'narrow', 'sectioned', 'ciniki.sapos.settings.menu');
    this.menu.sections = {
        'quote':{'label':'Quotes', 
            'visible':function() { return M.modFlagSet('ciniki.sapos', 0x010000); },
            'list':{
                'quote':{'label':'Quote Settings', 'fn':'M.ciniki_sapos_settings.quote.open(\'M.ciniki_sapos_settings.menu.open();\');'},
            }},
        'invoice':{'label':'Invoices', 
            'visible':function() { return M.modFlagAny('ciniki.sapos', 0x35); },
            'list':{
                'invoice':{'label':'Invoices', 'fn':'M.ciniki_sapos_settings.invoice.open(\'M.ciniki_sapos_settings.menu.open();\');'},
                'qi':{'label':'Quick Invoices', 'visible':'no', 'fn':'M.ciniki_sapos_settings.qi.open(\'M.ciniki_sapos_settings.menu.open();\');'},
                'rules':{'label':'Rules', 'visible':'no', 'fn':'M.ciniki_sapos_settings.showRules(\'M.ciniki_sapos_settings.menu.open();\',\'invoice\');'},
                'reports':{'label':'Reports', 
                    'visible':function() {return ((M.userPerms&0x01)>0?'yes':'no');},
                    'fn':'M.ciniki_sapos_settings.invoicereports.open(\'M.ciniki_sapos_settings.menu.open();\',\'invoicereports\');'},
                'categories':{'label':'Categories',
                    'visible':function() {return (M.modFlagOn('ciniki.sapos', 0x01000000) && (M.userPerms&0x01)>0?'yes':'no');},
                    'fn':'M.ciniki_sapos_settings.categories.open(\'M.ciniki_sapos_settings.menu.open();\');'},
            }},
        'shipments':{'label':'Shipments', 'visible':'no', 
            'visible':function() { return M.modFlagSet('ciniki.sapos', 0x40); },
            'list':{
                'shipments':{'label':'Settings', 'fn':'M.ciniki_sapos_settings.shipment.open(\'M.ciniki_sapos_settings.menu.open();\');'},
            }},
        'expenses':{'label':'Expenses', 'visible':'no', 
            'visible':function() { return M.modFlagSet('ciniki.sapos', 0x02); },
            'list':{
                'expenses':{'label':'Expense Categories', 'fn':'M.ciniki_sapos_settings.ecats.open(\'M.ciniki_sapos_settings.menu.open();\');'},
            }},
        'mileage':{'label':'Mileage', 'visible':'no', 
            'visible':function() { return M.modFlagSet('ciniki.sapos', 0x0100); },
            'list':{
                'mileagerates':{'label':'Rates', 'fn':'M.ciniki_sapos_settings.mrates.open(\'M.ciniki_sapos_settings.menu.open();\');'},
            }},
        'paypalapi':{'label':'Paypal API', 
            'visible':function() { return M.modFlagSet('ciniki.sapos', 0x0200); },
            'list':{
                'paypalapi':{'label':'Paypal', 'fn':'M.ciniki_sapos_settings.paypalapi.open(\'M.ciniki_sapos_settings.menu.open();\');'},
            }},
        'stripe':{'label':'', 
            'visible':function() { return M.modFlagSet('ciniki.sapos', 0x800000); },
            'list':{
                'paypalapi':{'label':'Stripe', 'fn':'M.ciniki_sapos_settings.stripe.open(\'M.ciniki_sapos_settings.menu.open();\');'},
            }},
        'paypalec':{'label':'Paypal Express Checkout', 
            'visible':function() { return M.modFlagSet('ciniki.sapos', 0x200000); },
            'list':{
                'paypalec':{'label':'Paypal', 'fn':'M.ciniki_sapos_settings.paypalec.open(\'M.ciniki_sapos_settings.menu.open();\');'},
            }},
        'paypalpayments':{'label':'Paypal Checkout', 
            'visible':function() { return M.modFlagSet('ciniki.sapos', 0x100000); },
            'list':{
                'paypalpayments':{'label':'Paypal', 'fn':'M.ciniki_sapos_settings.paypalpayments.open(\'M.ciniki_sapos_settings.menu.open();\');'},
            }},
        'donations':{'label':'Donations', 
            'visible':function() { return M.modFlagSet('ciniki.sapos', 0x02000000); },
            'list':{
                'packages':{'label':'Packages', 'fn':'M.ciniki_sapos_settings.packages.open(\'M.ciniki_sapos_settings.menu.open();\');'},
                'donations':{'label':'Settings', 'fn':'M.ciniki_sapos_settings.donations.open(\'M.ciniki_sapos_settings.menu.open();\');'},
            }},
    }
    this.menu.open = function(cb) {
/*        this.sections.invoice.list.qi.visible=(M.curTenant.modules['ciniki.sapos'].flags&0x04)>0?'yes':'no';
        this.sections.quote.visible=(M.curTenant.modules['ciniki.sapos'].flags&0x010000)>0?'yes':'no';
        this.sections.invoice.visible=(M.curTenant.modules['ciniki.sapos'].flags&0x35)>0?'yes':'no';
        this.sections.shipments.visible=(M.curTenant.modules['ciniki.sapos'].flags&0x40)>0?'yes':'no';
        this.sections.expenses.visible=(M.curTenant.modules['ciniki.sapos'].flags&0x02)>0?'yes':'no';
        this.sections.mileage.visible=(M.curTenant.modules['ciniki.sapos'].flags&0x100)>0?'yes':'no';
        this.sections.paypalapi.visible=(M.curTenant.modules['ciniki.sapos'].flags&0x0200)>0?'yes':'no';
        this.sections.paypalec.visible=(M.curTenant.modules['ciniki.sapos'].flags&0x200000)>0?'yes':'no';
        this.sections.paypalpayments.visible=(M.curTenant.modules['ciniki.sapos'].flags&0x100000)>0?'yes':'no'; */
        this.refresh();
        this.show(cb);
    }
    this.menu.addClose('Back');

    //
    // The invoice settings panel
    //
    this.quote = new M.panel('Quote Settings', 'ciniki_sapos_settings', 'quote', 'mc', 'medium', 'sectioned', 'ciniki.sapos.settings.quote');
    this.quote.sections = {
        '_options':{'label':'Quote Options', 'fields':{
            'quote-notes-product-synopsis':{'label':'Include Product Synopsis in Notes', 'default':'no', 'type':'toggle', 'toggles':this.yesNoOptions},
            }},
        '_bottom_msg':{'label':'Quote Message', 'fields':{
            'quote-bottom-message':{'label':'', 'hidelabel':'yes', 'type':'textarea'},
            }},
        '_footer_msg':{'label':'Footer Message', 'fields':{
            'quote-footer-message':{'label':'', 'hidelabel':'yes', 'type':'text'},
            }},
        '_textmsg':{'label':'Default Email Message', 'fields':{
            'quote-email-message':{'label':'', 'hidelabel':'yes', 'type':'textarea'},
            }},
        '_buttons':{'label':'', 'buttons':{
            'save':{'label':'Save', 'fn':'M.ciniki_sapos_settings.quote.save();'},
            }},
    }
    this.quote.fieldHistoryArgs = function(s, i) {
        return {'method':'ciniki.sapos.settingsHistory', 
            'args':{'tnid':M.curTenantID, 'setting':i}};
    }
    this.quote.fieldValue = function(s, i, d) {
        if( this.data[i] == null && d.default != null ) { return d.default; }
        return this.data[i];
    }
    this.quote.addDropImage = function(iid) {
        M.ciniki_sapos_settings.invoice.setFieldValue('invoice-header-image', iid);
        return true;
    }
    this.quote.deleteImage = function(fid) {
        this.setFieldValue(fid, 0);
        return true;
    }
    this.quote.open = function(cb) {
        M.api.getJSONCb('ciniki.sapos.settingsGet', {'tnid':M.curTenantID}, function(rsp) {
            if( rsp.stat != 'ok' ) {
                M.api.err(rsp);
                return false;
            }
            var p = M.ciniki_sapos_settings.quote;
            p.data = rsp.settings;
            p.refresh();
            p.show(cb);
        });
    }
    this.quote.save = function() {
        var c = this.serializeForm('no');
        if( c != '' ) {
            M.api.postJSONCb('ciniki.sapos.settingsUpdate', {'tnid':M.curTenantID}, c, function(rsp) {
                if( rsp.stat != 'ok' ) {
                    M.api.err(rsp);
                    return false;
                }
                M.ciniki_sapos_settings.quote.close();
            });
        } else {
            this.close();
        }
    }
    this.quote.addButton('save', 'Save', 'M.ciniki_sapos_settings.quote.save();');
    this.quote.addClose('Cancel');

    //
    // The invoice settings panel
    //
    this.invoice = new M.panel('Invoice Settings',
        'ciniki_sapos_settings', 'invoice',
        'mc', 'medium', 'sectioned', 'ciniki.sapos.settings.invoice');
    this.invoice.sections = {
        'image':{'label':'Header Image', 'fields':{
            'invoice-header-image':{'label':'', 'type':'image_id', 'hidelabel':'yes', 'controls':'all', 'history':'no'},
            }},
        'header':{'label':'Header Address Options', 'fields':{
            'invoice-header-contact-position':{'label':'Position', 'type':'toggle', 'default':'center', 'toggles':this.positionOptions},
            'invoice-header-tenant-name':{'label':'Tenant Name', 'type':'toggle', 'default':'yes', 'toggles':this.toggleOptions},
            'invoice-header-tenant-address':{'label':'Address', 'type':'toggle', 'default':'yes', 'toggles':this.toggleOptions},
            'invoice-header-tenant-phone':{'label':'Phone', 'type':'toggle', 'default':'yes', 'toggles':this.toggleOptions},
            'invoice-header-tenant-cell':{'label':'Cell', 'type':'toggle', 'default':'yes', 'toggles':this.toggleOptions},
            'invoice-header-tenant-fax':{'label':'Fax', 'type':'toggle', 'default':'yes', 'toggles':this.toggleOptions},
            'invoice-header-tenant-email':{'label':'Email', 'type':'toggle', 'default':'yes', 'toggles':this.toggleOptions},
            'invoice-header-tenant-website':{'label':'Website', 'type':'toggle', 'default':'yes', 'toggles':this.toggleOptions},
            }},
        '_bottom_msg':{'label':'Invoice Message', 'fields':{
            'invoice-bottom-message':{'label':'', 'hidelabel':'yes', 'type':'textarea'},
            }},
        '_packingslip_bottom_msg':{'label':'Packing Slip Message', 'fields':{
            'packingslip-bottom-message':{'label':'', 'hidelabel':'yes', 'type':'textarea'},
            }},
        '_footer_msg':{'label':'Footer Message', 'fields':{
            'invoice-footer-message':{'label':'', 'hidelabel':'yes', 'type':'text'},
            }},
        '_ui':{'label':'UI Options', 'fields':{
            'ui-options-print-picklist':{'label':'Pick List Button', 'default':'yes', 'type':'toggle', 'toggles':this.toggleOptions},
            'ui-options-print-invoice':{'label':'Print Invoice Button', 'default':'yes', 'type':'toggle', 'toggles':this.toggleOptions},
            'ui-options-print-envelope':{'label':'Print Envelope Button', 'default':'yes', 'type':'toggle', 'toggles':this.toggleOptions},
            }},
        '_rules':{'label':'Invoice Rules', 'fields':{
            'rules-invoice-duplicate-items':{'label':'Allow Duplicate Items', 'default':'yes', 'type':'toggle', 'toggles':this.yesNoOptions},
            'rules-invoice-paid-change-items':{'label':'Change Paid Invoice Items', 'default':'no', 'type':'toggle', 'toggles':this.yesNoOptions},
            'rules-invoice-submit-require-po_number':{'label':'Require PO Number', 'default':'no', 'type':'toggle', 'toggles':this.yesNoOptions},
            }},
        '_salesreps':{'label':'Sales Rep Rules', 'active':'no', 'fields':{
            'rules-salesreps-invoice-po_number':{'label':'PO Number', 'default':'view', 'type':'toggle', 'toggles':this.viewEditOptions},
            'rules-salesreps-invoice-pricepoint_id':{'label':'Pricepoint', 'default':'view', 'type':'toggle', 'toggles':this.viewEditOptions},
            'rules-salesreps-invoice-billing':{'label':'Billing Address', 'default':'view', 'type':'toggle', 'toggles':this.viewEditOptions},
            'rules-salesreps-invoice-shipping':{'label':'Shipping Address', 'default':'view', 'type':'toggle', 'toggles':this.viewEditOptions},
            'rules-salesreps-invoice-notes':{'label':'Customer Notes', 'default':'view', 'type':'toggle', 'toggles':this.viewEditOptions},
            }},
        '_invoice_email_options':{'label':'Invoice Email Options', 'fields':{
            'invoice-email-all-addresses':{'label':'Multiple emails per customer', 'type':'toggle', 'default':'no', 'toggles':{'no':'Send to one', 'yes':'Send to all'}},
            }},
        '_invoice_email_msg':{'label':'Default Invoice Email Message', 'fields':{
            'invoice-email-message':{'label':'', 'hidelabel':'yes', 'type':'textarea'},
            }},
        '_cart_email_msg':{'label':'Default Shopping Cart Email Message', 'fields':{
            'cart-email-message':{'label':'', 'hidelabel':'yes', 'type':'textarea'},
            }},
        '_pos_email_msg':{'label':'Default POS Receipt Email Message', 'fields':{
            'pos-email-message':{'label':'', 'hidelabel':'yes', 'type':'textarea'},
            }},
        '_order_email_msg':{'label':'Default Order Email Message', 'fields':{
            'order-email-message':{'label':'', 'hidelabel':'yes', 'type':'textarea'},

            }},
        '_buttons':{'label':'', 'buttons':{
            'save':{'label':'Save', 'fn':'M.ciniki_sapos_settings.invoice.save();'},
            }},
    }
    this.invoice.fieldHistoryArgs = function(s, i) {
        return {'method':'ciniki.sapos.settingsHistory', 
            'args':{'tnid':M.curTenantID, 'setting':i}};
    }
    this.invoice.fieldValue = function(s, i, d) {
        if( this.data[i] == null && d.default != null ) { return d.default; }
        return this.data[i];
    }
    this.invoice.addDropImage = function(iid) {
        M.ciniki_sapos_settings.invoice.setFieldValue('invoice-header-image', iid);
        return true;
    }
    this.invoice.deleteImage = function(fid) {
        this.setFieldValue(fid, 0);
        return true;
    }
    this.invoice.open = function(cb) {
        M.api.getJSONCb('ciniki.sapos.settingsGet', {'tnid':M.curTenantID}, function(rsp) {
            if( rsp.stat != 'ok' ) {
                M.api.err(rsp);
                return false;
            }
            var p = M.ciniki_sapos_settings.invoice;
            p.data = rsp.settings;
            p.refresh();
            p.show(cb);
        });
    }
    this.invoice.save = function() {
        var c = this.serializeForm('no');
        if( c != '' ) {
            M.api.postJSONCb('ciniki.sapos.settingsUpdate', {'tnid':M.curTenantID}, 
                c, function(rsp) {
                    if( rsp.stat != 'ok' ) {
                        M.api.err(rsp);
                        return false;
                    }
                    M.ciniki_sapos_settings.invoice.close();
                });
        } else {
            this.close();
        }
    }
    this.invoice.addButton('save', 'Save', 'M.ciniki_sapos_settings.invoice.save();');
    this.invoice.addClose('Cancel');

    //
    // The invoice settings panel
    //
    this.invoicereports = new M.panel('Invoice Settings',
        'ciniki_sapos_settings', 'invoicereports',
        'mc', 'medium', 'sectioned', 'ciniki.sapos.settings.invoicereports');
    this.invoicereports.sections = {
        'taxes':{'label':'Tax Reports', 'fields':{
            'invoice-reports-taxes-ontario-hst':{'label':'Ontario HST', 'type':'toggle', 'default':'center', 'toggles':this.yesNoOptions},
            }},
        '_buttons':{'label':'', 'buttons':{
            'save':{'label':'Save', 'fn':'M.ciniki_sapos_settings.invoicereports.save();'},
            }},
    }
    this.invoicereports.fieldHistoryArgs = function(s, i) {
        return {'method':'ciniki.sapos.settingsHistory', 'args':{'tnid':M.curTenantID, 'setting':i}};
    }
    this.invoicereports.fieldValue = function(s, i, d) {
        if( this.data[i] == null && d.default != null ) { return d.default; }
        return this.data[i];
    }
    this.invoicereports.open = function(cb) {
        M.api.getJSONCb('ciniki.sapos.settingsGet', {'tnid':M.curTenantID}, function(rsp) {
            if( rsp.stat != 'ok' ) {
                M.api.err(rsp);
                return false;
            }
            var p = M.ciniki_sapos_settings.invoicereports;
            p.data = rsp.settings;
            p.refresh();
            p.show(cb);
        });
    }
    this.invoicereports.save = function() {
        var c = this.serializeForm('no');
        if( c != '' ) {
            M.api.postJSONCb('ciniki.sapos.settingsUpdate', {'tnid':M.curTenantID}, c, function(rsp) {
                if( rsp.stat != 'ok' ) {
                    M.api.err(rsp);
                    return false;
                }
                M.ciniki_sapos_settings.invoicereports.close();
            });
        } else {
            this.close();
        }
    }
    this.invoicereports.addButton('save', 'Save', 'M.ciniki_sapos_settings.invoicereports.save();');
    this.invoicereports.addClose('Cancel');

    //
    // The invoice settings panel
    //
    this.shipment = new M.panel('Shipment Settings',
        'ciniki_sapos_settings', 'shipment',
        'mc', 'medium', 'sectioned', 'ciniki.sapos.settings.shipment');
    this.shipment.sections = {
        '_defaults':{'label':'Defaults', 'fields':{
            'shipments-default-shipper':{'label':'Shipper', 'type':'text'},
            'shipments-default-weight-units':{'label':'Units', 'type':'toggle', 'default':'10', 'toggles':this.weightUnits},
            'shipments-hide-weight-units':{'label':'Hide Units', 'type':'toggle', 'default':'no', 'toggles':{'no':'No', 'yes':'Yes'}},
            }},
        '_rules':{'label':'Shipment Rules', 'fields':{
            'rules-shipment-shipped-require-weight':{'label':'Require Weight', 'default':'no', 'type':'toggle', 'toggles':this.yesNoOptions},
            'rules-shipment-shipped-require-tracking_number':{'label':'Require Tracking #', 'default':'no', 'type':'toggle', 'toggles':this.yesNoOptions},
            'rules-shipment-shipped-require-boxes':{'label':'Require Boxes', 'default':'no', 'type':'toggle', 'toggles':this.yesNoOptions},
            }},
        '_buttons':{'label':'', 'buttons':{
            'save':{'label':'Save', 'fn':'M.ciniki_sapos_settings.shipment.save();'},
            }},
    }
    this.shipment.fieldHistoryArgs = function(s, i) {
        return {'method':'ciniki.sapos.settingsHistory', 
            'args':{'tnid':M.curTenantID, 'setting':i}};
    }
    this.shipment.fieldValue = function(s, i, d) {
        if( this.data[i] == null && d.default != null ) { return d.default; }
        return this.data[i];
   }
    this.shipment.open = function(cb) {
        M.api.getJSONCb('ciniki.sapos.settingsGet', {'tnid':M.curTenantID}, function(rsp) {
            if( rsp.stat != 'ok' ) {
                M.api.err(rsp);
                return false;
            }
            var p = M.ciniki_sapos_settings.shipment;
            p.data = rsp.settings;
            p.refresh();
            p.show(cb);
        });
    }
    this.shipment.save = function() {
        var c = this.serializeForm('no');
        if( c != '' ) {
            M.api.postJSONCb('ciniki.sapos.settingsUpdate', {'tnid':M.curTenantID}, 
                c, function(rsp) {
                    if( rsp.stat != 'ok' ) {
                        M.api.err(rsp);
                        return false;
                    }
                    M.ciniki_sapos_settings.shipment.close();
                });
        } else {
            this.close();
        }
    }
    this.shipment.addButton('save', 'Save', 'M.ciniki_sapos_settings.shipment.save();');
    this.shipment.addClose('Cancel');

    //
    // The qi settings panel
    //
    this.qi = new M.panel('Quick Invoice',
        'ciniki_sapos_settings', 'qi',
        'mc', 'medium', 'sectioned', 'ciniki.sapos.settings.qi');
    this.qi.sections = {
        'items':{'label':'Items', 'type':'simplegrid', 'num_cols':2,
            'addTxt':'Add',
            'addFn':'M.ciniki_sapos_settings.qiedit.open(\'M.ciniki_sapos_settings.qi.open();\',0);',
            }
    }
    this.qi.sectionData = function(s) { return this.data[s]; }
    this.qi.cellValue = function(s, i, j, d) {
        switch(j) {
            case 0: return d.item.name;
            case 1: return d.item.unit_amount;
        }
    }
    this.qi.rowFn = function(s, i, d) {
        return 'M.ciniki_sapos_settings.qiedit.open(\'M.ciniki_sapos_settings.qi.open();\',\'' + d.item.id + '\');';
    }
    this.qi.open = function(cb) {
        M.api.getJSONCb('ciniki.sapos.qiItemList', {'tnid':M.curTenantID}, function(rsp) {
            if( rsp.stat != 'ok' ) {
                M.api.err(rsp);
                return false;
            }
            var p = M.ciniki_sapos_settings.qi;
            p.data = {'items':rsp.items};
            p.refresh();
            p.show(cb);
        });
    }
    this.qi.addButton('add', 'Add', 'M.ciniki_sapos_settings.qiedit.open(\'M.ciniki_sapos_settings.qi.open();\',0);');
    this.qi.addClose('Back');

    //
    // The qi item edit panel
    //
    this.qiedit = new M.panel('Expense Category',
        'ciniki_sapos_settings', 'qiedit',
        'mc', 'medium', 'sectioned', 'ciniki.sapos.settings.qiedit');
    this.qiedit.item_id = 0;
    this.qiedit.data = {};
    this.qiedit.sections = {
        'item':{'label':'Item', 'fields':{
            'name':{'label':'Name', 'type':'text'},
            'description':{'label':'Description', 'type':'text'},
            'quantity':{'label':'Quantity', 'type':'text', 'size':'small'},
            'unit_amount':{'label':'Unit Amount', 'type':'text', 'size':'small'},
            'unit_discount_amount':{'label':'Discount Amount', 'type':'text', 'size':'small'},
            'unit_discount_percentage':{'label':'Discount Percentage', 'type':'text', 'size':'small'},
            'taxtype_id':{'label':'Taxes', 'type':'select', 'options':{}},
            }},
        '_buttons':{'label':'', 'buttons':{
            'save':{'label':'Save', 'fn':'M.ciniki_sapos_settings.qiedit.save();'},
            'delete':{'label':'Delete', 'fn':'M.ciniki_sapos_settings.qiedit.remove(M.ciniki_sapos_settings.qiedit.item_id);'},
            }},
    }
    this.qiedit.fieldValue = function(s, i, d) {
        if( this.data[i] == null ) { return ''; }
        return this.data[i];
    }
    this.qiedit.fieldHistoryArgs = function(s, i) {
        return {'method':'ciniki.sapos.history', 'args':{'tnid':M.curTenantID,
            'object':'ciniki.sapos.qi_item', 'object_id':this.item_id, 'field':i}};
    }
    this.qiedit.open = function(cb, iid) {
        if( M.curTenant.modules['ciniki.taxes'] != null ) {
            M.api.getJSONCb('ciniki.taxes.typeList', {'tnid':M.curTenantID}, function(rsp) {
                if( rsp.stat != 'ok' ) {
                    M.api.err(rsp);
                    return false;
                }
                var p = M.ciniki_sapos_settings.qiedit;
                p.sections.item.fields.taxtype_id.options[0] = 'No Taxes';
                for(i in rsp.active) {
                    p.sections.item.fields.taxtype_id.options[rsp.active[i].type.id] = rsp.active[i].type.name + ((rsp.active[i].type.rates==''||rsp.active[i].type.rates==null)?', No Taxes':', ' + rsp.active[i].type.rates);
                }
                M.ciniki_sapos_settings.qiedit.openFinish(cb, iid);
            });
        } else {
            this.openFinish(cb, iid);
        }
    }
    this.qiedit.openFinish = function(cb, qid) {
        if( qid != null ) { this.item_id = qid; }
        if( this.item_id > 0 ) {
            this.sections._buttons.buttons.delete.visible='yes';
            M.api.getJSONCb('ciniki.sapos.qiItemGet', {'tnid':M.curTenantID, 'item_id':this.item_id}, function(rsp) {
                if( rsp.stat != 'ok' ) {
                    M.api.err(rsp);
                    return false;
                }
                var p = M.ciniki_sapos_settings.qiedit;
                p.data = rsp.item;
                p.refresh();
                p.show(cb);
            });
        } else {
            this.reset();
            this.data = {};
            this.sections._buttons.buttons.delete.visible='no';
            this.refresh();
            this.show(cb);
        }
    }
    this.qiedit.save = function() {
        if( this.item_id > 0 ) {
            var c = this.serializeForm('no');
            if( c != '' ) {
                M.api.postJSONCb('ciniki.sapos.qiItemUpdate', {'tnid':M.curTenantID, 'item_id':this.item_id}, c, function(rsp) {
                    if( rsp.stat != 'ok' ) {
                        M.api.err(rsp);
                        return false;
                    }
                    M.ciniki_sapos_settings.qiedit.close();
                });
            } else {
                this.close();
            }
        } else {
            var c = this.serializeForm('yes');
            M.api.postJSONCb('ciniki.sapos.qiItemAdd', {'tnid':M.curTenantID}, c, function(rsp) {
                if( rsp.stat != 'ok' ) {
                    M.api.err(rsp);
                    return false;
                }
                M.ciniki_sapos_settings.qiedit.close();
            });
        }
    }
    this.qiedit.remove = function(cid) {
        if( confirm('Are you sure you want to remove this category?') ) {
            M.api.getJSONCb('ciniki.sapos.qiItemDelete', {'tnid':M.curTenantID, 'item_id':cid}, function(rsp) {
                if( rsp.stat != 'ok' ) {
                    M.api.err(rsp);
                    return false;
                }
                M.ciniki_sapos_settings.qiedit.close();
            });
        }
    }
    this.qiedit.addClose('Cancel');

    //
    // The expenses settings panel
    //
    this.ecats = new M.panel('Expense Categories',
        'ciniki_sapos_settings', 'ecats',
        'mc', 'medium', 'sectioned', 'ciniki.sapos.settings.ecats');
    this.ecats.sections = {
        'categories':{'label':'Categories', 'type':'simplegrid', 'num_cols':1,
            'addTxt':'Add',
            'addFn':'M.ciniki_sapos_settings.ecatedit.open(\'M.ciniki_sapos_settings.ecats.open();\',0);',
            }
    }
    this.ecats.sectionData = function(s) { return this.data[s]; }
    this.ecats.cellValue = function(s, i, j, d) {
        switch(j) {
            case 0: return d.category.name;
        }
    }
    this.ecats.rowFn = function(s, i, d) {
        return 'M.ciniki_sapos_settings.ecatedit.open(\'M.ciniki_sapos_settings.ecats.open();\',\'' + d.category.id + '\');';
    }
    this.ecats.open = function(cb) {
        M.api.getJSONCb('ciniki.sapos.expenseCategoryList', 
            {'tnid':M.curTenantID}, function(rsp) {
                if( rsp.stat != 'ok' ) {
                    M.api.err(rsp);
                    return false;
                }
                var p = M.ciniki_sapos_settings.ecats;
                p.data = {'categories':rsp.categories};
                p.refresh();
                p.show(cb);
            });
    }
    this.ecats.addButton('add', 'Add', 'M.ciniki_sapos_settings.ecatedit.open(\'M.ciniki_sapos_settings.ecats.open();\',0);');
    this.ecats.addClose('Back');

    //
    // The expense category edit panel
    //
    this.ecatedit = new M.panel('Expense Category', 'ciniki_sapos_settings', 'ecatedit', 'mc', 'medium', 'sectioned', 'ciniki.sapos.settings.ecatedit');
    this.ecatedit.category_id = 0;
    this.ecatedit.data = {};
    this.ecatedit.sections = {
        'category':{'label':'Category', 'fields':{
            'sequence':{'label':'Sequence', 'type':'text', 'size':'small'},
            'name':{'label':'Name', 'type':'text', 'size':'medium'},
            'flags':{'label':'Options', 'type':'flags', 
                'visible':function() { return M.modOn('ciniki.taxes'); },
                'flags':{'1':{'name':'Tax'}}},
            }},
        '_buttons':{'label':'', 'buttons':{
            'save':{'label':'Save', 'fn':'M.ciniki_sapos_settings.ecatedit.save();'},
            'delete':{'label':'Delete', 'fn':'M.ciniki_sapos_settings.ecatedit.remove(M.ciniki_sapos_settings.ecatedit.category_id);'},
            }},
    }
    this.ecatedit.fieldValue = function(s, i, d) {
        if( this.data[i] == null ) { return ''; }
        return this.data[i];
    }
    this.ecatedit.open = function(cb, cid) {
        if( cid != null ) { this.category_id = cid; }
        if( this.category_id > 0 ) {
            this.sections._buttons.buttons.delete.visible='yes';
            M.api.getJSONCb('ciniki.sapos.expenseCategoryGet', {'tnid':M.curTenantID,
                'category_id':this.category_id}, function(rsp) {
                    if( rsp.stat != 'ok' ) {
                        M.api.err(rsp);
                        return false;
                    }
                    var p = M.ciniki_sapos_settings.ecatedit;
                    p.data = rsp.category;
                    p.refresh();
                    p.show(cb);
                });
        } else {
            this.reset();
            this.data = {};
            this.sections._buttons.buttons.delete.visible='no';
            this.refresh();
            this.show(cb);
        }
    }
    this.ecatedit.save = function() {
        if( this.category_id > 0 ) {
            var c = this.serializeForm('no');
            if( c != '' ) {
                M.api.postJSONCb('ciniki.sapos.expenseCategoryUpdate', {'tnid':M.curTenantID,
                    'category_id':this.category_id}, c, function(rsp) {
                        if( rsp.stat != 'ok' ) {
                            M.api.err(rsp);
                            return false;
                        }
                        M.ciniki_sapos_settings.ecatedit.close();
                    });
            } else {
                this.close();
            }
        } else {
            var c = this.serializeForm('yes');
            M.api.postJSONCb('ciniki.sapos.expenseCategoryAdd', {'tnid':M.curTenantID},
                c, function(rsp) {
                    if( rsp.stat != 'ok' ) {
                        M.api.err(rsp);
                        return false;
                    }
                    M.ciniki_sapos_settings.ecatedit.close();
                });
        }
    }
    this.ecatedit.remove = function(cid) {
        if( confirm('Are you sure you want to remove this category?') ) {
            M.api.getJSONCb('ciniki.sapos.expenseCategoryDelete', {'tnid':M.curTenantID,
                'category_id':cid}, function(rsp) {
                    if( rsp.stat != 'ok' ) {
                        M.api.err(rsp);
                        return false;
                    }
                    M.ciniki_sapos_settings.ecatedit.close();
                });
        }
    }
    this.ecatedit.addClose('Cancel');

    //
    // The mileage rates settings panel
    //
    this.mrates = new M.panel('Mileage Rates', 'ciniki_sapos_settings', 'mrates', 'mc', 'medium', 'sectioned', 'ciniki.sapos.settings.mrates');
    this.mrates.sections = {
        'rates':{'label':'Mileage Rates', 'type':'simplegrid', 'num_cols':3,
            'headerValues':['Rate', 'Start', 'End'],
            'addTxt':'Add',
            'addFn':'M.ciniki_sapos_settings.mrateedit.open(\'M.ciniki_sapos_settings.mrates.open();\',0);',
            }
    }
    this.mrates.sectionData = function(s) { return this.data[s]; }
    this.mrates.cellValue = function(s, i, j, d) {
        switch(j) {
            case 0: return d.rate.rate_display;
            case 1: return d.rate.start_date;
            case 2: return d.rate.end_date;
        }
    }
    this.mrates.rowFn = function(s, i, d) {
        return 'M.ciniki_sapos_settings.mrateedit.open(\'M.ciniki_sapos_settings.mrates.open();\',\'' + d.rate.id + '\');';
    }
    this.mrates.open = function(cb) {
        M.api.getJSONCb('ciniki.sapos.mileageRateList', {'tnid':M.curTenantID}, function(rsp) {
            if( rsp.stat != 'ok' ) {
                M.api.err(rsp);
                return false;
            }
            var p = M.ciniki_sapos_settings.mrates;
            p.data = {'rates':rsp.rates};
            p.refresh();
            p.show(cb);
        });
    }
    this.mrates.addButton('add', 'Add', 'M.ciniki_sapos_settings.mrateedit.open(\'M.ciniki_sapos_settings.mrates.open();\',0);');
    this.mrates.addClose('Back');

    //
    // The expense category edit panel
    //
    this.mrateedit = new M.panel('Expense Category', 'ciniki_sapos_settings', 'mrateedit', 'mc', 'medium', 'sectioned', 'ciniki.sapos.settings.mrateedit');
    this.mrateedit.rate_id = 0;
    this.mrateedit.data = {};
    this.mrateedit.sections = {
        '_rate':{'label':'Mileage Rate', 'fields':{
            'rate':{'label':'Rate/km', 'type':'text', 'size':'small'},
            'start_date':{'label':'Start Date', 'type':'date', 'size':'medium'},
            'end_date':{'label':'End Date', 'type':'date', 'size':'medium'},
            }},
        '_buttons':{'label':'', 'buttons':{
            'save':{'label':'Save', 'fn':'M.ciniki_sapos_settings.mrateedit.save();'},
            'delete':{'label':'Delete', 'fn':'M.ciniki_sapos_settings.mrateedit.remove(M.ciniki_sapos_settings.mrateedit.rate_id);'},
            }},
    }
    this.mrateedit.fieldValue = function(s, i, d) {
        if( this.data[i] == null ) { return ''; }
        return this.data[i];
    }
    this.mrateedit.fieldHistoryArgs = function(s, i) {
        return {'method':'ciniki.sapos.history', 'args':{'tnid':M.curTenantID,
            'object':'ciniki.sapos.mileage_rate', 'object_id':this.rate_id, 'field':i}};
    }
    this.mrateedit.open = function(cb, rid) {
        if( rid != null ) { this.rate_id = rid; }
        if( this.rate_id > 0 ) {
            this.sections._buttons.buttons.delete.visible='yes';
            M.api.getJSONCb('ciniki.sapos.mileageRateGet', {'tnid':M.curTenantID, 'rate_id':this.rate_id}, function(rsp) {
                if( rsp.stat != 'ok' ) {
                    M.api.err(rsp);
                    return false;
                }
                var p = M.ciniki_sapos_settings.mrateedit;
                p.data = rsp.rate;
                p.refresh();
                p.show(cb);
            });
        } else {
            this.reset();
            this.data = {};
            this.sections._buttons.buttons.delete.visible='no';
            this.refresh();
            this.show(cb);
        }
    }
    this.mrateedit.save = function() {
        if( this.rate_id > 0 ) {
            var c = this.serializeForm('no');
            if( c != '' ) {
                M.api.postJSONCb('ciniki.sapos.mileageRateUpdate', {'tnid':M.curTenantID, 'rate_id':this.rate_id}, c, function(rsp) {
                    if( rsp.stat != 'ok' ) {
                        M.api.err(rsp);
                        return false;
                    }
                    M.ciniki_sapos_settings.mrateedit.close();
                });
            } else {
                this.close();
            }
        } else {
            var c = this.serializeForm('yes');
            M.api.postJSONCb('ciniki.sapos.mileageRateAdd', {'tnid':M.curTenantID}, c, function(rsp) {
                if( rsp.stat != 'ok' ) {
                    M.api.err(rsp);
                    return false;
                }
                M.ciniki_sapos_settings.mrateedit.close();
            });
        }
    }
    this.mrateedit.remove = function(rid) {
        if( confirm('Are you sure you want to remove this rate?') ) {
            M.api.getJSONCb('ciniki.sapos.mileageRateDelete', {'tnid':M.curTenantID, 'rate_id':rid}, function(rsp) {
                if( rsp.stat != 'ok' ) {
                    M.api.err(rsp);
                    return false;
                }
                M.ciniki_sapos_settings.mrateedit.close();
            });
        }
    }
    this.mrateedit.addClose('Cancel');

    //
    // The paypal API settings panel
    //
    this.paypalapi = new M.panel('Paypal API', 'ciniki_sapos_settings', 'paypalapi', 'mc', 'medium', 'sectioned', 'ciniki.sapos.settings.paypalapi');
    this.paypalapi.sections = {
//          'paypal':{'label':'Paypal', 'fields':{
//              'paypal-api-processing':{'label':'Virtual Terminal', 'type':'toggle', 'default':'no', 'toggles':this.toggleOptions},
//              }},
        'test':{'label':'Test Credentials', 'fields':{
            'paypal-test-account':{'label':'Account', 'type':'text'},
            'paypal-test-endpoint':{'label':'Endpoint', 'type':'text'},
            'paypal-test-clientid':{'label':'Client ID', 'type':'text'},
            'paypal-test-secret':{'label':'Secret', 'type':'text'},
            }},
        'live':{'label':'Live Credentials', 'fields':{
            'paypal-live-endpoint':{'label':'Endpoint', 'type':'text'},
            'paypal-live-clientid':{'label':'Client ID', 'type':'text'},
            'paypal-live-secret':{'label':'Secret', 'type':'text'},
            }},
        '_buttons':{'label':'', 'buttons':{
            'save':{'label':'Save', 'fn':'M.ciniki_sapos_settings.paypalapi.save();'},
            }},
    }
    this.paypalapi.fieldHistoryArgs = function(s, i) {
        return {'method':'ciniki.sapos.settingsHistory', 
            'args':{'tnid':M.curTenantID, 'setting':i}};
    }
    this.paypalapi.fieldValue = function(s, i, d) {
        return this.data[i];
    }
    this.paypalapi.open = function(cb) {
        M.api.getJSONCb('ciniki.sapos.settingsGet', {'tnid':M.curTenantID}, function(rsp) {
            if( rsp.stat != 'ok' ) {
                M.api.err(rsp);
                return false;
            }
            var p = M.ciniki_sapos_settings.paypalapi;
            p.data = rsp.settings;
            p.refresh();
            p.show(cb);
        });
    }
    this.paypalapi.save = function() {
        var c = this.serializeForm('no');
        if( c != '' ) {
            M.api.postJSONCb('ciniki.sapos.settingsUpdate', {'tnid':M.curTenantID}, c, function(rsp) {
                if( rsp.stat != 'ok' ) {
                    M.api.err(rsp);
                    return false;
                }
                M.ciniki_sapos_settings.paypalapi.close();
            });
        } else {
            this.close();
        }
    }
    this.paypalapi.addButton('save', 'Save', 'M.ciniki_sapos_settings.paypalapi.save();');
    this.paypalapi.addClose('Cancel');

    //
    // The paypal Express Checkout settings panel
    //
    this.paypalec = new M.panel('Paypal EC',
        'ciniki_sapos_settings', 'paypalec',
        'mc', 'medium', 'sectioned', 'ciniki.sapos.settings.paypalec');
    this.paypalec.sections = {
        'paypal':{'label':'Paypal', 'fields':{
            'paypal-ec-site':{'label':'Site', 'type':'toggle', 'default':'sandbox', 'toggles':{'sandbox':'Sandbox', 'live':'Live'}},
            }},
        'credentials':{'label':'Credentials', 'fields':{
//              'paypal-live-endpoint':{'label':'Endpoint', 'type':'text'},
            'paypal-ec-clientid':{'label':'Username', 'type':'text'},
            'paypal-ec-password':{'label':'Password', 'type':'text'},
            'paypal-ec-signature':{'label':'Signature', 'type':'text'},
            }},
        '_buttons':{'label':'', 'buttons':{
            'save':{'label':'Save', 'fn':'M.ciniki_sapos_settings.paypalec.save();'},
            }},
    }
    this.paypalec.fieldHistoryArgs = function(s, i) {
        return {'method':'ciniki.sapos.settingsHistory', 
            'args':{'tnid':M.curTenantID, 'setting':i}};
    }
    this.paypalec.fieldValue = function(s, i, d) {
        return this.data[i];
    }
    this.paypalec.open = function(cb) {
        M.api.getJSONCb('ciniki.sapos.settingsGet', {'tnid':M.curTenantID}, function(rsp) {
            if( rsp.stat != 'ok' ) {
                M.api.err(rsp);
                return false;
            }
            var p = M.ciniki_sapos_settings.paypalec;
            p.data = rsp.settings;
            p.refresh();
            p.show(cb);
        });
    }
    this.paypalec.save = function() {
        var c = this.serializeForm('no');
        if( c != '' ) {
            M.api.postJSONCb('ciniki.sapos.settingsUpdate', {'tnid':M.curTenantID}, c, function(rsp) {
                if( rsp.stat != 'ok' ) {
                    M.api.err(rsp);
                    return false;
                }
                M.ciniki_sapos_settings.paypalec.close();
            });
        } else {
            this.close();
        }
    }
    this.paypalec.addButton('save', 'Save', 'M.ciniki_sapos_settings.paypalec.save();');
    this.paypalec.addClose('Cancel');

    //
    // The paypal checkout settings panel
    //
    this.paypalpayments = new M.panel('Paypal Payments',
        'ciniki_sapos_settings', 'paypalpayments',
        'mc', 'medium', 'sectioned', 'ciniki.sapos.settings.paypalpayments');
    this.paypalpayments.sections = {
        'paypal':{'label':'Paypal', 'fields':{
            'paypal-site':{'label':'Site', 'type':'toggle', 'default':'test', 'toggles':{'test':'Sandbox', 'live':'Live'}},
            'paypal-business':{'label':'Account Email', 'type':'text'},
            'paypal-prefix':{'label':'Prefix', 'type':'text'},
            }},
        '_buttons':{'label':'', 'buttons':{
            'save':{'label':'Save', 'fn':'M.ciniki_sapos_settings.paypalpayments.save();'},
            }},
    }
    this.paypalpayments.fieldHistoryArgs = function(s, i) {
        return {'method':'ciniki.sapos.settingsHistory', 
            'args':{'tnid':M.curTenantID, 'setting':i}};
    }
    this.paypalpayments.fieldValue = function(s, i, d) {
        return this.data[i];
    }
    this.paypalpayments.open = function(cb) {
        M.api.getJSONCb('ciniki.sapos.settingsGet', {'tnid':M.curTenantID}, function(rsp) {
            if( rsp.stat != 'ok' ) {
                M.api.err(rsp);
                return false;
            }
            var p = M.ciniki_sapos_settings.paypalpayments;
            p.data = rsp.settings;
            p.refresh();
            p.show(cb);
        });
    }
    this.paypalpayments.save = function() {
        var c = this.serializeForm('no');
        if( c != '' ) {
            M.api.postJSONCb('ciniki.sapos.settingsUpdate', {'tnid':M.curTenantID}, c, function(rsp) {
                if( rsp.stat != 'ok' ) {
                    M.api.err(rsp);
                    return false;
                }
                M.ciniki_sapos_settings.paypalpayments.close();
            });
        } else {
            this.close();
        }
    }
    this.paypalpayments.addButton('save', 'Save', 'M.ciniki_sapos_settings.paypalpayments.save();');
    this.paypalpayments.addClose('Cancel');

    //
    // The Stripe settings panel
    //
    this.stripe = new M.panel('Stripe', 'ciniki_sapos_settings', 'stripe', 'mc', 'medium', 'sectioned', 'ciniki.sapos.settings.stripe');
    this.stripe.sections = {
        'stripe':{'label':'Paypal', 'fields':{
            'stripe-pk':{'label':'Publishable key', 'type':'text'},
            'stripe-sk':{'label':'Secret key', 'type':'text'},
            }},
        '_buttons':{'label':'', 'buttons':{
            'save':{'label':'Save', 'fn':'M.ciniki_sapos_settings.stripe.save();'},
            }},
    }
    this.stripe.fieldHistoryArgs = function(s, i) {
        return {'method':'ciniki.sapos.settingsHistory', 'args':{'tnid':M.curTenantID, 'setting':i}};
    }
    this.stripe.fieldValue = function(s, i, d) {
        return this.data[i];
    }
    this.stripe.open = function(cb) {
        M.api.getJSONCb('ciniki.sapos.settingsGet', {'tnid':M.curTenantID}, function(rsp) {
            if( rsp.stat != 'ok' ) {
                M.api.err(rsp);
                return false;
            }
            var p = M.ciniki_sapos_settings.stripe;
            p.data = rsp.settings;
            p.refresh();
            p.show(cb);
        });
    }
    this.stripe.save = function() {
        var c = this.serializeForm('no');
        if( c != '' ) {
            M.api.postJSONCb('ciniki.sapos.settingsUpdate', {'tnid':M.curTenantID}, c, function(rsp) {
                if( rsp.stat != 'ok' ) {
                    M.api.err(rsp);
                    return false;
                }
                M.ciniki_sapos_settings.stripe.close();
            });
        } else {
            this.close();
        }
    }
    this.stripe.addButton('save', 'Save', 'M.ciniki_sapos_settings.stripe.save();');
    this.stripe.addClose('Cancel');

    //
    // The donation settings panel
    //
    this.donations = new M.panel('Settings', 'ciniki_sapos_settings', 'donations', 'mc', 'medium', 'sectioned', 'ciniki.sapos.settings.main');
    this.donations.sections = {
//        'image':{'label':'Header Image', 'fields':{
//            'donation-receipt-header-image':{'label':'', 'type':'image_id', 'hidelabel':'yes', 'controls':'all', 'history':'no'},
//            }},
//        'header':{'label':'Header Address Options', 'fields':{
//            'donation-receipt-header-contact-position':{'label':'Position', 'type':'toggle', 'default':'center', 'toggles':this.positionOptions},
//            'donation-receipt-header-tenant-name':{'label':'Tenant Name', 'type':'toggle', 'default':'yes', 'toggles':this.toggleOptions},
//            'donation-receipt-header-tenant-address':{'label':'Address', 'type':'toggle', 'default':'yes', 'toggles':this.toggleOptions},
//          'donation-receipt-header-tenant-phone':{'label':'Phone', 'type':'toggle', 'default':'yes', 'toggles':this.toggleOptions},
//          'donation-receipt-header-tenant-cell':{'label':'Cell', 'type':'toggle', 'default':'yes', 'toggles':this.toggleOptions},
//          'donation-receipt-header-tenant-fax':{'label':'Fax', 'type':'toggle', 'default':'yes', 'toggles':this.toggleOptions},
//          'donation-receipt-header-tenant-email':{'label':'Email', 'type':'toggle', 'default':'yes', 'toggles':this.toggleOptions},
//          'donation-receipt-header-tenant-website':{'label':'Website', 'type':'toggle', 'default':'yes', 'toggles':this.toggleOptions},
//            }},
        '_charity_info':{'label':'Donation Receipts', 'fields':{
            'donation-receipt-minimum-amount':{'label':'Minimum for receipt', 'type':'text', 'size':'small'},
            'donation-receipt-invoice-include':{'label':'Include on invoices', 'type':'toggle', 'default':'no', 'toggles':this.yesNoOptions},
            'donation-receipt-signing-officer':{'label':'Signing Officer', 'type':'text'},
            'donation-receipt-charity-number':{'label':'Charity Number', 'type':'text'},
            'donation-receipt-location-issued':{'label':'Location Issued', 'type':'text'},
            }},
        '_invoice_msg':{'label':'Invoice Message', 'fields':{
            'donation-invoice-message':{'label':'', 'hidelabel':'yes', 'type':'text'},
            }},
        '_thank_you_msg':{'label':'Receipt Thank You Message', 'fields':{
            'donation-receipt-thankyou-message':{'label':'', 'hidelabel':'yes', 'type':'text'},
            }},
        'image':{'label':'Signature Image', 'fields':{
            'donation-receipt-signature-image':{'label':'', 'type':'image_id', 'hidelabel':'yes', 'controls':'all', 'history':'no'},
            }},
        '_buttons':{'label':'', 'buttons':{
            'save':{'label':'Save', 'fn':'M.ciniki_sapos_settings.donations.save();'},
            }},
    }
    this.donations.fieldHistoryArgs = function(s, i) {
        return {'method':'ciniki.donations.settingsHistory', 'args':{'tnid':M.curTenantID, 'setting':i}};
    }
    this.donations.fieldValue = function(s, i, d) {
        if( this.data[i] == null && d.default != null ) { return d.default; }
        return this.data[i];
    }
    this.donations.addDropImage = function(iid) {
        M.ciniki_sapos_settings.donations.setFieldValue('donation-receipt-signature-image', iid);
        return true;
    }
    this.donations.deleteImage = function(fid) {
        this.setFieldValue(fid, 0);
        return true;
    }
    this.donations.open = function(cb) {
        M.api.getJSONCb('ciniki.sapos.settingsGet', {'tnid':M.curTenantID}, function(rsp) {
            if( rsp.stat != 'ok' ) {
                M.api.err(rsp);
                return false;
            }
            var p = M.ciniki_sapos_settings.donations;
            p.data = rsp.settings;
            p.refresh();
            p.show(cb);
        });
    }
    this.donations.save = function() {
        var c = this.serializeForm('no');
        if( c != '' ) {
            M.api.postJSONCb('ciniki.sapos.settingsUpdate', {'tnid':M.curTenantID}, 
                c, function(rsp) {
                    if( rsp.stat != 'ok' ) {
                        M.api.err(rsp);
                        return false;
                    }
                    M.ciniki_sapos_settings.donations.close();
                });
        } else {
            this.close();
        }
    }
    this.donations.addButton('save', 'Save', 'M.ciniki_sapos_settings.donations.save();');
    this.donations.addClose('Cancel');

    //
    // The panel to list the package
    //
    this.packages = new M.panel('Packages', 'ciniki_sapos_settings', 'packages', 'mc', 'medium', 'sectioned', 'ciniki.sapos.settings.packages');
    this.packages.data = {};
    this.packages.nplist = [];
    this.packages.sections = {
//        'search':{'label':'', 'type':'livesearchgrid', 'livesearchcols':1,
//            'cellClasses':[''],
//            'hint':'Search package',
//            'noData':'No package found',
//            },
        'packages':{'label':'Donation Package', 'type':'simplegrid', 'num_cols':1,
            'noData':'No package',
            'addTxt':'Add Donation Package',
            'addFn':'M.ciniki_sapos_settings.package.open(\'M.ciniki_sapos_settings.packages.open();\',0,null);'
            },
    }
/*    this.packages.liveSearchCb = function(s, i, v) {
        if( s == 'search' && v != '' ) {
            M.api.getJSONBgCb('ciniki.donations.packageSearch', {'tnid':M.curTenantID, 'start_needle':v, 'limit':'25'}, function(rsp) {
                M.ciniki_sapos_settings.packages.liveSearchShow('search',null,M.gE(M.ciniki_sapos_settings.packages.panelUID + '_' + s), rsp.packages);
                });
        }
    }
    this.packages.liveSearchResultValue = function(s, f, i, j, d) {
        return d.name;
    }
    this.packages.liveSearchResultRowFn = function(s, f, i, j, d) {
        return 'M.ciniki_sapos_settings.package.open(\'M.ciniki_sapos_settings.packages.open();\',\'' + d.id + '\');';
    } */
    this.packages.cellValue = function(s, i, j, d) {
        if( s == 'packages' ) {
            switch(j) {
                case 0: return d.name;
            }
        }
    }
    this.packages.rowFn = function(s, i, d) {
        if( s == 'packages' ) {
            return 'M.ciniki_sapos_settings.package.open(\'M.ciniki_sapos_settings.packages.open();\',\'' + d.id + '\',M.ciniki_sapos_settings.package.nplist);';
        }
    }
    this.packages.open = function(cb) {
        M.api.getJSONCb('ciniki.sapos.packageList', {'tnid':M.curTenantID}, function(rsp) {
            if( rsp.stat != 'ok' ) {
                M.api.err(rsp);
                return false;
            }
            var p = M.ciniki_sapos_settings.packages;
            p.data = rsp;
            p.nplist = (rsp.nplist != null ? rsp.nplist : null);
            p.refresh();
            p.show(cb);
        });
    }
    this.packages.addClose('Back');

    //
    // The panel to edit Donation Package
    //
    this.package = new M.panel('Donation Package', 'ciniki_sapos_settings', 'package', 'mc', 'medium', 'sectioned', 'ciniki.sapos.main.package');
    this.package.data = null;
    this.package.package_id = 0;
    this.package.nplist = [];
    this.package.sections = {
/*        '_primary_image_id':{'label':'Image', 'type':'imageform', 'aside':'yes', 'fields':{
            'primary_image_id':{'label':'', 'type':'image_id', 'hidelabel':'yes', 'controls':'all', 'history':'no',
                'addDropImage':function(iid) {
                    M.ciniki_sapos_settings.package.setFieldValue('primary_image_id', iid);
                    return true;
                    },
                'addDropImageRefresh':'',
                'removeImage':function(fid) {
                    M.ciniki_sapos_settings.package.setFieldValue(fid,0);
                    return true;
                 },
             },
        }},*/
        'general':{'label':'', 'aside':'yes', 'fields':{
            'name':{'label':'Name', 'required':'yes', 'type':'text'},
            'subname':{'label':'Price Display', 'type':'text', 'size':'small'},
            'sequence':{'label':'Order', 'type':'text', 'size':'small'},
            'flags1':{'label':'Visible', 'type':'flagtoggle', 'field':'flags', 'bit':0x01, 'default':'off'},
            'flags2':{'label':'Fixed Amount', 'type':'flagtoggle', 'field':'flags', 'bit':0x02, 'default':'on', 'on_fields':['amount']},
//            'category':{'label':'Category', 'type':'text'},
            'amount':{'label':'Amount', 'type':'text', 'size':'medium'},
            'invoice_name':{'label':'Invoice Description', 'required':'yes', 'type':'text'},
            }},
        '_synopsis':{'label':'Synopsis', 'fields':{
            'synopsis':{'label':'', 'hidelabel':'yes', 'type':'textarea', 'size':'small'},
            }},
//        '_description':{'label':'Description', 'fields':{
//            'description':{'label':'', 'hidelabel':'yes', 'type':'textarea'},
//            }},
        '_buttons':{'label':'', 'buttons':{
            'save':{'label':'Save', 'fn':'M.ciniki_sapos_settings.package.save();'},
            'delete':{'label':'Delete', 
                'visible':function() {return M.ciniki_sapos_settings.package.package_id > 0 ? 'yes' : 'no'; },
                'fn':'M.ciniki_sapos_settings.package.remove();'},
            }},
        }
    this.package.fieldValue = function(s, i, d) { return this.data[i]; }
    this.package.fieldHistoryArgs = function(s, i) {
        return {'method':'ciniki.sapos.packageHistory', 'args':{'tnid':M.curTenantID, 'package_id':this.package_id, 'field':i}};
    }
    this.package.open = function(cb, pid, list) {
        if( pid != null ) { this.package_id = pid; }
        if( list != null ) { this.nplist = list; }
        M.api.getJSONCb('ciniki.sapos.packageGet', {'tnid':M.curTenantID, 'package_id':this.package_id}, function(rsp) {
            if( rsp.stat != 'ok' ) {
                M.api.err(rsp);
                return false;
            }
            var p = M.ciniki_sapos_settings.package;
            p.data = rsp.package;
            p.sections.general.fields.amount.visible = ((rsp.package.flags&0x02) == 0x02 ? 'yes' : 'no');
            p.refresh();
            p.show(cb);
        });
    }
    this.package.save = function(cb) {
        if( cb == null ) { cb = 'M.ciniki_sapos_settings.package.close();'; }
        if( !this.checkForm() ) { return false; }
        if( this.package_id > 0 ) {
            var c = this.serializeForm('no');
            if( c != '' ) {
                M.api.postJSONCb('ciniki.sapos.packageUpdate', {'tnid':M.curTenantID, 'package_id':this.package_id}, c, function(rsp) {
                    if( rsp.stat != 'ok' ) {
                        M.api.err(rsp);
                        return false;
                    }
                    eval(cb);
                });
            } else {
                eval(cb);
            }
        } else {
            var c = this.serializeForm('yes');
            M.api.postJSONCb('ciniki.sapos.packageAdd', {'tnid':M.curTenantID}, c, function(rsp) {
                if( rsp.stat != 'ok' ) {
                    M.api.err(rsp);
                    return false;
                }
                M.ciniki_sapos_settings.package.package_id = rsp.id;
                eval(cb);
            });
        }
    }
    this.package.remove = function() {
        if( confirm('Are you sure you want to remove package?') ) {
            M.api.getJSONCb('ciniki.sapos.packageDelete', {'tnid':M.curTenantID, 'package_id':this.package_id}, function(rsp) {
                if( rsp.stat != 'ok' ) {
                    M.api.err(rsp);
                    return false;
                }
                M.ciniki_sapos_settings.package.close();
            });
        }
    }
    this.package.nextButtonFn = function() {
        if( this.nplist != null && this.nplist.indexOf('' + this.package_id) < (this.nplist.length - 1) ) {
            return 'M.ciniki_sapos_settings.package.save(\'M.ciniki_sapos_settings.package.open(null,' + this.nplist[this.nplist.indexOf('' + this.package_id) + 1] + ');\');';
        }
        return null;
    }
    this.package.prevButtonFn = function() {
        if( this.nplist != null && this.nplist.indexOf('' + this.package_id) > 0 ) {
            return 'M.ciniki_sapos_settings.package.save(\'M.ciniki_sapos_settings.package_id.open(null,' + this.nplist[this.nplist.indexOf('' + this.package_id) - 1] + ');\');';
        }
        return null;
    }
    this.package.addButton('save', 'Save', 'M.ciniki_sapos_settings.package.save();');
    this.package.addClose('Cancel');
    this.package.addButton('next', 'Next');
    this.package.addLeftButton('prev', 'Prev');

    //
    // The panel to manage auto categorization of invoice items
    //
    this.categories = new M.panel('Auto Category Items', 'ciniki_sapos_settings', 'categories', 'mc', 'medium', 'sectioned', 'ciniki.sapos.main.categories');
    this.categories.data = null;
    this.categories.categories_id = 0;
    this.categories.nplist = [];
    this.categories.sections = {
        'categories':{'label':'', 'fields':{
            }},
        '_buttons':{'label':'', 'buttons':{
            'save':{'label':'Save', 'fn':'M.ciniki_sapos_settings.categories.save();'},
            'delete':{'label':'Delete', 
                'visible':function() {return M.ciniki_sapos_settings.categories.categories_id > 0 ? 'yes' : 'no'; },
                'fn':'M.ciniki_sapos_settings.categories.remove();'},
            }},
        }
    this.categories.fieldValue = function(s, i, d) { return this.data[i]; }
    this.categories.fieldHistoryArgs = function(s, i) {
        return {'method':'ciniki.sapos.categoriesHistory', 'args':{'tnid':M.curTenantID, 'field':i}};
    }
    this.categories.open = function(cb, pid, list) {
        if( pid != null ) { this.categories_id = pid; }
        if( list != null ) { this.nplist = list; }
        M.api.getJSONCb('ciniki.sapos.categoriesGet', {'tnid':M.curTenantID}, function(rsp) {
            if( rsp.stat != 'ok' ) {
                M.api.err(rsp);
                return false;
            }
            var p = M.ciniki_sapos_settings.categories;
            p.data = {};
            p.sections.categories.fields = {};
            for(var i in rsp.categories) {
                p.sections.categories.fields[rsp.categories[i].field] = {'label':rsp.categories[i].label, 'type':'text'};
                p.data[rsp.categories[i].field] = rsp.categories[i].value;
            }
            p.refresh();
            p.show(cb);
        });
    }
    this.categories.save = function(cb) {
        if( cb == null ) { cb = 'M.ciniki_sapos_settings.categories.close();'; }
        var c = this.serializeForm('no');
        if( c != '' ) {
            M.api.postJSONCb('ciniki.sapos.categoriesUpdate', {'tnid':M.curTenantID}, c, function(rsp) {
                if( rsp.stat != 'ok' ) {
                    M.api.err(rsp);
                    return false;
                }
                eval(cb);
            });
        } else {
            eval(cb);
        }
    }
    this.categories.addClose('Cancel');

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
        var appContainer = M.createContainer(appPrefix, 'ciniki_sapos_settings', 'yes');
        if( appContainer == null ) {
            alert('App Error');
            return false;
        } 

        //
        // Decide what should be visible
        //
        this.invoice.sections._salesreps.active = ((M.curTenant.modules['ciniki.sapos'].flags&0x0800) > 0?'yes':'no');
        this.invoice.sections._invoice_email_msg.active=M.curTenant.modules['ciniki.mail']!=null&&(M.curTenant.modules['ciniki.sapos'].flags&0x01)>0?'yes':'no';
        this.invoice.sections._invoice_email_options.active=M.curTenant.modules['ciniki.mail']!=null&&(M.curTenant.modules['ciniki.sapos'].flags&0x01)>0&&(M.userPerms&0x01)==0x01?'yes':'no';
        this.invoice.sections._cart_email_msg.active=M.curTenant.modules['ciniki.mail']!=null&&(M.curTenant.modules['ciniki.sapos'].flags&0x04)>0?'yes':'no';
        this.invoice.sections._pos_email_msg.active=M.curTenant.modules['ciniki.mail']!=null&&(M.curTenant.modules['ciniki.sapos'].flags&0x10)>0?'yes':'no';
        this.invoice.sections._order_email_msg.active=M.curTenant.modules['ciniki.mail']!=null&&(M.curTenant.modules['ciniki.sapos'].flags&0x20)>0?'yes':'no';
        this.quote.sections._textmsg.active=M.curTenant.modules['ciniki.mail']!=null?'yes':'no';

        if( args.ecats != null && args.ecats == 'yes' ) {
            this.ecats.open(cb);
        } else {
            this.menu.open(cb);
        }
    }
}
