//
// This panel will create or edit an invoice
//
function ciniki_sapos_pos() {
    this.invoiceTypes = {};
    this.invoiceStatuses = {
        '10':'Entered',
        '20':'Pending Manufacturing',
        '30':'Pending Shipping',
        '40':'Payment Required',
        '50':'Paid',
        '55':'Refund Required',
        '60':'Refunded',
        '65':'Void',
        };
    this.paymentStatuses = {
        '0':'None Required',
        '10':'Required',
        '40':'Deposit',
        '50':'Paid',
        '55':'Refund Required',
        '60':'Refunded',
        };
    this.donationreceiptStatuses = {
        '0':'N/A',
        '20':'Pending',
        '40':'Printed',
        '60':'Mailed',
        '80':'Received',
        };
    this.taxTypeOptions = {};
    this.transactionTypes = {
        '10':'Deposit',
        '20':'Payment',
        '60':'Refund',
        };
    this.invoiceFlags = {
        '1':{'name':'Hide Savings'},
        };

    //
    // The menu panel for checkout
    //
    this.menu = new M.panel('Checkout Sales',
        'ciniki_sapos_pos', 'menu',
        'mc', 'large', 'sectioned', 'ciniki.sapos.pos.menu');
    this.menu.data = {};
    this.menu.sections = {
        'packing_required':{'label':'Packing', 'type':'simplegrid', 'num_cols':2, 'aside':'yes',
            'visible':function() { return M.modFlagSet('ciniki.sapos', 0x20000000); },
            'headerValues':['#', 'Customer'],
            'noData':'No Orders',
            },
        'pickups':{'label':'Pending Pickups', 'type':'simplegrid', 'num_cols':2, 'aside':'yes',
            'visible':function() { return M.modFlagSet('ciniki.sapos', 0x20000000); },
            'headerValues':['#', 'Customer'],
            'noData':'No Pending Pickups',
            },
        'invoices':{'label':'Todays Sales', 'type':'simplegrid', 'num_cols':4,
            'headerValues':['#', 'Status', 'Customer', 'Total'],
            'headerClasses':['', '', '', 'alignright'],
            'footerClasses':['', '', '', 'alignright'],
            'cellClasses':['', '', '', 'alignright'],
            'addTxt':'Add Sale',
            'addTopFn':'M.ciniki_sapos_pos.checkout.open(\'M.ciniki_sapos_pos.menu.open();\',0);',
            },
    }
    this.menu.cellValue = function(s, i, j, d) {
        if( s == 'packing_required' ) {
            switch(j) {
                case 0: return d.invoice_number;
                case 1: return d.billing_name;
            }
        }
        if( s == 'pickups' ) {
            switch(j) {
                case 0: return d.invoice_number;
                case 1: return d.billing_name;
            }
        }
        if( s == 'invoices' ) {
            switch(j) {
                case 0: return d.invoice_number;
                case 1: return d.status_text;
                case 2: return d.billing_name;
                case 3: return d.total_amount_display;
            }
        }
    }
    this.menu.rowFn = function(s, i, d) {
        if( d == null ) {
            return '';
        }
        return 'M.ciniki_sapos_pos.checkout.open(\'M.ciniki_sapos_pos.menu.open();\',\'' + d.id + '\');';
    }
    this.menu.footerValue = function(s, i, d) {
        if( s == 'invoices' ) {
            if( i == 3 ) { return this.data.totals.total_amount_display; }
            return '';
        }
        return null;
    }
    this.menu.autoUpdate = function() {
        if( this.isVisible() ) {
            M.api.getJSONCb('ciniki.sapos.posSales', {'tnid':M.curTenantID}, function(rsp) {
                if( rsp.stat != 'ok' ) {
                    M.api.err(rsp);
                    return false;
                }
                var p = M.ciniki_sapos_pos.menu;
                p.data = rsp;
                p.refreshSections('packing_required');
                p.refreshSections('pickups');
                setTimeout('M.ciniki_sapos_pos.menu.autoUpdate();', 60000);
            });
        }
    }
    this.menu.open = function(cb, v) {
        M.api.getJSONCb('ciniki.sapos.posSales', {'tnid':M.curTenantID}, function(rsp) {
            if( rsp.stat != 'ok' ) {
                M.api.err(rsp);
                return false;
            }
            var p = M.ciniki_sapos_pos.menu;
            p.data = rsp;
            p.refresh();
            p.show(cb);
            setTimeout('M.ciniki_sapos_pos.menu.autoUpdate();', 60000);
        });
    }
    this.menu.addButton('add', 'Add', 'M.ciniki_sapos_pos.checkout.open(\'M.ciniki_sapos_pos.menu.open();\',0);');
    this.menu.addClose('Back');

    //
    // The invoice panel
    //
    this.checkout = new M.panel('Checkout',
        'ciniki_sapos_pos', 'checkout',
        'mc', 'large mediumaside', 'sectioned', 'ciniki.sapos.pos.checkout');
    this.checkout.invoice_id = 0;
    this.checkout.data = {};
    this.checkout.liveSearchRN = 0;
    this.checkout.searchResults = [];
    this.checkout.sections = {
        'details':{'label':'', 'type':'simplegrid', 'aside':'yes', 'num_cols':2,
            'cellClasses':['label',''],
            },
        '_orderbuttons':{'label':'', 'aside':'yes',
            'visible':function() { return M.ciniki_sapos_pos.checkout.data.status == 45 && M.ciniki_sapos_pos.checkout.data.shipping_status <= 55 ? 'yes' : 'no'; },
            'buttons':{
                'packed':{'label':'Order Packed - Ready for Pickup', 
                    'visible':function() {return M.ciniki_sapos_pos.checkout.data.status == 45 && M.ciniki_sapos_pos.checkout.data.shipping_status == 20 ? 'yes' : 'no'; },
                    'fn':'M.ciniki_sapos_pos.checkout.orderPacked();',
                    },
                'pickedup':{'label':'Order Picked Up', 
                    'visible':function() {return M.ciniki_sapos_pos.checkout.data.status == 45 && M.ciniki_sapos_pos.checkout.data.shipping_status == 55 ? 'yes' : 'no'; },
                    'fn':'M.ciniki_sapos_pos.checkout.orderPickedUp();',
                    },
            }},
        'customer_details':{'label':'', 'aside':'yes', 'type':'simplegrid', 'num_cols':2,
            'cellClasses':['label',''],
            'addTxt':'Edit',
            'addFn':'M.startApp(\'ciniki.customers.edit\',null,\'M.ciniki_sapos_pos.checkout.open();\',\'mc\',{\'next\':\'M.ciniki_sapos_pos.checkout.updateCustomer\',\'action\':\'edit\',\'customer_id\':M.ciniki_sapos_pos.checkout.data.customer_id});',
            'changeTxt':'Change customer',
            'changeFn':'M.startApp(\'ciniki.customers.edit\',null,\'M.ciniki_sapos_pos.checkout.open();\',\'mc\',{\'next\':\'M.ciniki_sapos_pos.checkout.updateCustomer\',\'action\':\'change\',\'current_id\':M.ciniki_sapos_pos.checkout.data.customer_id,\'customer_id\':0});',
            },
        'membership_details':{'label':'Membership', 'type':'simplegrid', 'aside':'yes', 'num_cols':2,
            'visible':function() { return (M.modFlagOn('ciniki.customers', 0x08) && M.ciniki_sapos_pos.checkout.data.customer_id > 0 ? 'yes' : 'no'); },
            'cellClasses':['label',''],
            },
        'item_search':{'label':'', 'type':'livesearchgrid', 'livesearchcols':4,
            'visible':function() { return M.ciniki_sapos_pos.checkout.data.status < 45 ? 'yes' : 'no'; },
            'headerClasses':['', '', 'alignright', 'alignright'],
            'headerValues':['Code', 'Description', 'Price', ''],
            'cellClasses':['', 'multiline', 'alignright', 'alignright'],
            'hint':'Search Items',
            'autofocus':'yes',
            },
        'items':{'label':'', 'type':'simplegrid', 'num_cols':5,
            'headerValues':['#', 'Code', 'Description', 'Quantity/Price', 'Total'],
            'headerClasses':['', '', '', 'alignright', 'alignright'],
            'cellClasses':['alignright','', 'multiline', 'multiline alignright', 'multiline alignright'],
            'sortable':'yes',
            'sortTypes':['number', 'text','text','number','number'],
            'addTxt':'Add',
            'addFn':'M.ciniki_sapos_pos.item.open(\'M.ciniki_sapos_pos.checkout.open();\',0,M.ciniki_sapos_pos.checkout.invoice_id);',
            },
        'tallies':{'label':'', 'type':'simplegrid', 'num_cols':2,
            'headerValues':null,
            'cellClasses':['alignright','alignright'],
            },
        'transactions':{'label':'Payments', 'type':'simplegrid', 'num_cols':3,
            'visible':function() {return M.ciniki_sapos_pos.checkout.data.total_amount > 0 && M.ciniki_sapos_pos.checkout.data.transactions.length > 0 ? 'yes':'no'},
            'headerValues':null,
            'cellClasses':['', '', 'alignright'],
            },
        'messages':{'label':'Messages', 'type':'simplegrid', 'num_cols':2,
            'visible':function() {return M.ciniki_sapos_pos.checkout.data.total_amount > 0 && M.ciniki_sapos_pos.checkout.data.balance_amount == 0 && M.ciniki_sapos_pos.checkout.data.customer_id > 0 ?'yes':'no'},
            'cellClasses':['multiline', 'multiline'],
            'addTxt':'Email Receipt',
            'addFn':'M.ciniki_sapos_pos.email.open(\'M.ciniki_sapos_pos.checkout.open();\',M.ciniki_sapos_pos.checkout.data);',
            },
        '_buttons':{'label':'', 'buttons':{
            'record':{'label':'Record Transaction', 
                'visible':function() {return M.ciniki_sapos_pos.checkout.data.balance_amount > 0 && M.ciniki_sapos_pos.checkout.data.items.length > 0 ?'yes':'no'},
                'fn':'M.ciniki_sapos_pos.transaction.open(\'M.ciniki_sapos_pos.checkout.open();\',0,M.ciniki_sapos_pos.checkout.invoice_id,\'now\',M.ciniki_sapos_pos.checkout.data.balance_amount_display);',
                },
            'completesale':{'label':'Complete Sale', 
                'visible':function() {return M.ciniki_sapos_pos.checkout.data.balance_amount == 0 && M.ciniki_sapos_pos.checkout.data.items.length > 0 ?'yes':'no'},
                'fn':'M.ciniki_sapos_pos.checkout.completeSale();',
                },
            'print':{'label':'Print Receipt', 
                'visible':function() {return M.ciniki_sapos_pos.checkout.data.total_amount > 0 && M.ciniki_sapos_pos.checkout.data.balance_amount == 0 ?'yes':'no'},
                'fn':'M.ciniki_sapos_pos.checkout.printReceipt();',
                },
            'printdonationreceipt':{'label':'Print Donation Receipt', 
                'visible':function() {return M.ciniki_sapos_pos.checkout.data.total_amount > 0 && M.ciniki_sapos_pos.checkout.data.balance_amount == 0 && M.ciniki_sapos_pos.checkout.data.donations != null && M.ciniki_sapos_pos.checkout.data.donations == 'yes' ? 'yes' : 'no'; },
                'fn':'M.ciniki_sapos_pos.checkout.printDonationReceipt();',
                },
            'delete':{'label':'Delete Sale', 
                'visible':function() {return M.ciniki_sapos_pos.checkout.data.total_amount == 0 && M.ciniki_sapos_pos.checkout.data.items.length == 0 ? 'yes' : 'no'; },
                'fn':'M.ciniki_sapos_pos.checkout.deleteInvoice();',
                },

            }},
        };
    this.checkout.liveSearchCb = function(s, i, v) {
        this.liveSearchRN++;
        var sN = this.liveSearchRN;
        M.api.getJSONBgCb('ciniki.sapos.invoiceItemSearch', {'tnid':M.curTenantID,
            'field':i, 'invoice_id':M.ciniki_sapos_pos.checkout.invoice_id, 'start_needle':v, 'limit':15}, function(rsp) {
                if( sN == M.ciniki_sapos_pos.checkout.liveSearchRN ) {
                    M.ciniki_sapos_pos.checkout.searchResults = rsp.items;
                    M.ciniki_sapos_pos.checkout.liveSearchShow(s,null,M.gE(M.ciniki_sapos_pos.checkout.panelUID + '_' + s), rsp.items);
                }
            });
    }
    this.checkout.liveSearchResultValue = function(s,f,i,j,d) {
        switch(j) {
            case 0: return d.item.code;
            case 1: 
                if( d.item.notes != null && d.item.notes != '' ) {
                    return '<span class="maintext">' + d.item.description + '</span><span class="subtext">' + d.item.notes.replace(/\n/g, '<br/>') + '</span>';
                }
                return d.item.description;
            case 2: return d.item.unit_amount;
            case 3: return '<button onclick="M.ciniki_sapos_pos.checkout.addItem(event,\'' + d.item.object + '\',\'' + d.item.object_id + '\');">Add</button>';
        }
    };
    this.checkout.liveSearchSubmitFn = function(s, v) {
        M.gE(this.panelUID + '_item_search_livesearch_grid').style.display = 'none';
        this.liveSearchRN++;
        if( this.liveSearchTimer != null ) {
            clearTimeout(this.liveSearchTimer);
        }
        if( v == '' ) {
            return;
        }
        M.ciniki_sapos_pos.searchresults.open('M.ciniki_sapos_pos.checkout.open();', v);
    }
    this.checkout.cellColour = function(s, i, j, d) {
        if( s == 'membership_details' && j == 1 && d.expires != null ) {
            switch(d.expires) {
                case 'past': return '#ffdddd';
                case 'soon': return '#ffefdd';
                case 'future': return '#ddffdd';
            }
        }
        return '';
    }
    this.checkout.cellValue = function(s, i, j, d) {
        if( s == 'details' || s == 'customer_details' ) {
            switch (j) {
                case 0: return d.label;
                case 1: return (d.label == 'Email'?M.linkEmail(d.value):d.value);
            }
        }
        if( s == 'membership_details' ) {
            switch(j) {
                case 0: return d.label;
                case 1: 
                    if( d.expiry_display != null && d.expiry_display != '' ) {
                        return d.value + '<span class="subdue"> (' + d.expiry_display + ')</span>';
                    }
                    return d.value;
            }
        }
        if( s == 'items' ) {
            if( j == 0 ) {
                return '<span class="subdue">' + (parseInt(i) + 1) + '</span>';
            }
            else if( j == 1 ) {
                return d.code;
            }
            else if( j == 2 ) {
                if( d.notes != null && d.notes != '' ) {
                    return '<span class="maintext">' + d.description + '</span><span class="subtext">' + d.notes.replace(/\n/g, '<br/>') + '</span>';
                }
                return d.description;
            }
            else if( j == 3 ) {
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
            else if( j == 4 ) {
                return '<span class="maintext">' + d.total_amount_display + '</span><span class="subtext">' + ((d.taxtype_name!=null)?d.taxtype_name:'') + '</span>';
            }
        }
        if( s == 'tallies' ) {
            switch(j) {
                case 0: return d.description;
                case 1: return d.amount;
            }
        }
        if( s == 'transactions' ) {
            switch(j) {
                case 0: return d.transaction_type_text + ((d.source_text!=null&&d.source_text!='')?(' - ' + d.source_text):'');
                case 1: return d.transaction_date;
                case 2: return ((d.transaction_type==60)?'-':'')+d.customer_amount;
//                  case 3: return d.transaction_fees;
//                  case 4: return d.tenant_amount;
            }
        }
        if( s == 'messages' ) {
            switch(j) {
                case 0: return '<span class="maintext">' + d.message.status_text + '</span><span class="subtext">' + d.message.date_sent + '</span>';
                case 1: return '<span class="maintext">' + d.message.customer_email + '</span><span class="subtext">' + d.message.subject + '</span>';
            }
        }
    };
    this.checkout.rowFn = function(s, i, d) {
        if( d == null ) {
            return '';
        }
        if( s == 'membership_details' ) {
            return null;
        }
        if( s == 'customer_details' ) { return ''; }
        if( s == 'items' ) {
            if( d.object == 'ciniki.courses.offering_registration' ) {
                return 'M.startApp(\'ciniki.courses.sapos\',null,\'M.ciniki_sapos_pos.checkout.open();\',\'mc\',{\'item_object\':\'' + d.object + '\',\'item_object_id\':\'' + d.object_id + '\',\'source\':\'pos\'});';
            } else if( M.ciniki_sapos_pos.checkout.data.status < 45 ) {
                return 'M.ciniki_sapos_pos.item.open(\'M.ciniki_sapos_pos.checkout.open();\',\'' + d.id + '\');';
            }
        }
        if( s == 'tallies' ) {
            return '';
        }
        if( s == 'transactions' ) {
            if( d.id > 0 ) {
                return 'M.ciniki_sapos_pos.transaction.open(\'M.ciniki_sapos_pos.checkout.open();\',\'' + d.id + '\',0);';
            } 
            return '';
        }
        return '';
    }
    this.checkout.updateCustomer = function(cid) {
        // If the customer has changed, then update the details of the invoice
        if( cid != null && this.data.customer_id != cid ) {
            // Update the customer attached to the invoice, and update shipping/billing records for the invoice
            M.api.getJSONCb('ciniki.sapos.pos', {'tnid':M.curTenantID, 'invoice_id':this.invoice_id, 'action':'updatecustomer', 'customer_id':cid}, this.processOpen);
        } else {
            M.api.getJSONCb('ciniki.sapos.pos', {'tnid':M.curTenantID, 'invoice_id':this.invoice_id, 'action':'updatecustomer'}, this.processOpen);
        }
    }
    this.checkout.addItem = function(e,o,i) {
        if( e != null ) {
            e.stopPropagation();
            if( e.screenX == 0 && e.screenY == 0 ) {
                return false;
            }
        }
        M.gE(this.panelUID + '_item_search').value = '';
        M.gE(this.panelUID + '_item_search_livesearch_grid').style.display = 'none';
        M.api.getJSONCb('ciniki.sapos.pos', {'tnid':M.curTenantID, 
            'invoice_id':this.invoice_id, 'action':'additem', 'object':o, 'object_id':i, 'quantity':1}, this.processOpen);
    }
    this.checkout.printDonationReceipt = function(iid) {
        M.showPDF('ciniki.sapos.donationPDF', {'tnid':M.curTenantID, 'invoice_id':this.invoice_id});
    }
    this.checkout.printReceipt = function(iid) {
        if( iid <= 0 ) { return false; }
        M.showPDF('ciniki.sapos.invoicePDF', {'tnid':M.curTenantID, 'invoice_id':this.invoice_id});
    }
    this.checkout.orderPacked = function() {
        M.api.getJSONCb('ciniki.sapos.invoiceAction', {'tnid':M.curTenantID,
            'invoice_id':this.invoice_id, 'action':'packed'}, function(rsp) {
                if( rsp.stat != 'ok' ) {
                    M.api.err(rsp);
                    return false;
                }
                M.ciniki_sapos_pos.checkout.close();
            });
    }
    this.checkout.orderPickedUp = function() {
        M.api.getJSONCb('ciniki.sapos.invoiceAction', {'tnid':M.curTenantID,
            'invoice_id':this.invoice_id, 'action':'pickedup'}, function(rsp) {
                if( rsp.stat != 'ok' ) {
                    M.api.err(rsp);
                    return false;
                }
                M.ciniki_sapos_pos.checkout.close();
            });
    }
    this.checkout.completeSale = function() {
        M.api.getJSONCb('ciniki.sapos.invoiceAction', {'tnid':M.curTenantID,
            'invoice_id':this.invoice_id, 'action':'completesale'}, function(rsp) {
                if( rsp.stat != 'ok' ) {
                    M.api.err(rsp);
                    return false;
                }
                M.ciniki_sapos_pos.checkout.close();
            });
    }
    this.checkout.open = function(cb,iid) {
        if( cb != null ) { this.cb = cb; }
        if( iid != null ) { this.invoice_id = iid; }
        M.api.getJSONCb('ciniki.sapos.pos', {'tnid':M.curTenantID, 'invoice_id':this.invoice_id}, this.processOpen);
    }
    this.checkout.deleteInvoice = function() {
        M.api.getJSONCb('ciniki.sapos.pos', {'tnid':M.curTenantID, 'invoice_id':this.invoice_id, 'action':'deleteempty'}, function(rsp) {
            if( rsp.stat != 'ok' ) {
                M.api.err(rsp);
                return false;
            }
            M.ciniki_sapos_pos.checkout.close(); 
        });
    }
    this.checkout.processOpen = function(rsp) {
        if( rsp.stat != 'ok' ) {
            M.api.err(rsp);
            return false;
        }
        var p = M.ciniki_sapos_pos.checkout;
        p.data = rsp.invoice;
        p.invoice_id = rsp.invoice.id;
        if( rsp.invoice.customer_id > 0 ) {
            p.sections.customer_details.addTxt = 'Edit Customer';
            p.sections.customer_details.changeTxt = 'Change Customer';
        } else {
            p.sections.customer_details.addTxt = 'Add Customer';
            p.sections.customer_details.changeTxt = '';
        }
        p.sections.tallies.visible='yes';
        p.data.tallies = {};
        if( rsp.invoice.discount_amount > 0 || (rsp.invoice.taxes != null && rsp.invoice.taxes.length > 0) ) {
            p.data.tallies['subtotal'] = {'description':'Sub Total', 'amount':(rsp.invoice.subtotal_amount!=null)?rsp.invoice.subtotal_amount_display:'0.00'};
        }
        if( rsp.invoice.discount_amount > 0 ) {
            var discount = '';
            if( rsp.invoice.subtotal_discount_amount != 0 ) {
                discount = '-' + rsp.invoice.subtotal_discount_amount_display;
            }
            if( rsp.invoice.subtotal_discount_percentage != 0 ) {
                discount += ((rsp.invoice.subtotal_discount_amount != 0)?', ':'') + '-' + rsp.invoice.subtotal_discount_percentage + '%';
            }
            p.data.tallies['discount_amount'] = {'description':'Overall discount (' + discount + ')', 'amount':(rsp.invoice.discount_amount!=null)?'-'+rsp.invoice.discount_amount_display:'0.00'};
        }
        if( rsp.invoice.taxes != null ) {
            for(i in rsp.invoice.taxes) {
                p.data.tallies['tax'+i] = {'description':rsp.invoice.taxes[i].description, 'amount':rsp.invoice.taxes[i].amount_display};
            }
        }
        if( p.data.tallies.length == 1 ) {
            p.data.tallies = {};
        }
        p.data.tallies['total'] = {'description':'Total', 'amount':rsp.invoice.total_amount_display};
        if( rsp.invoice.total_savings > 0 && (rsp.invoice.flags&0x01) == 0 ) {
            p.data.tallies['savings'] = {'description':'Savings', 'amount':rsp.invoice.total_savings_display};
        }
        if( rsp.invoice.transactions != null && rsp.invoice.transactions.length > 0 ) {
            p.sections.transactions.visible='yes';
            p.data.transactions = rsp.invoice.transactions;
            if( rsp.invoice.balance_amount_display != null ) {
                p.data.transactions.push({'id':'0', 
                    'transaction_date':'Balance Owing', 
                    'transaction_type':0,
                    'transaction_type_text':'',
                    'customer_amount':rsp.invoice.balance_amount_display});
            }
        } else {
            p.sections.transactions.visible='no';
        }
        if( rsp.invoice.status < 45 ) {
            p.sections.items.addTxt = 'Add';
        } else {
            p.sections.items.addTxt = '';
        }
        p.refresh();
        p.show();
    }
    this.checkout.addClose('Back');

    //
    // The item edit panel
    //
    this.item = new M.panel('Invoice Item', 'ciniki_sapos_pos', 'item', 'mc', 'medium', 'sectioned', 'ciniki.sapos.pos.item');
    this.item.item_id = 0;
    this.item.object = '';
    this.item.object_id = 0;
    this.item.price_id = 0;
    this.item.data = {};
    this.item.liveSearchRN = 0;
    this.item.search_timer = null;
    this.item.search_section = '';
    this.item.search_field = '';
    this.item.search_value = '';
    this.item.sections = {
        'details':{'label':'', 'fields':{
            'code':{'label':'Code', 'type':'text', 'livesearch':'yes', 
                'livesearchcols':3,
                },
            'description':{'label':'Description', 'required':'yes', 'type':'text', 'livesearch':'yes', 
                'livesearchcols':3,
                },
            'quantity':{'label':'Quantity', 'required':'yes', 'type':'text', 'size':'small'},
            'unit_amount':{'label':'Price', 'required':'yes', 'type':'text', 'size':'small'},
            'unit_discount_amount':{'label':'Discount Amount', 'type':'text', 'size':'small'},
            'unit_discount_percentage':{'label':'Discount %', 'type':'text', 'size':'small'},
            'flags1':{'label':'Donation', 'type':'flagtoggle', 'field':'flags', 'bit':0x8000, 'default':'off', 
                'on_fields':[],
                'active':function() { return M.modFlagSet('ciniki.sapos', 0x02000000); },
                },
            'donation_category':{'label':'Donation Category', 'type':'text', 'visible':'no', },
            'taxtype_id':{'label':'Taxes', 'type':'select', 'options':{}},
            }},
        '_notes':{'label':'Notes', 'fields':{
            'notes':{'label':'', 'hidelabel':'yes', 'type':'textarea', 'size':'medium'},
            }},
        '_buttons':{'label':'', 'buttons':{
            'save':{'label':'Save', 'fn':'M.ciniki_sapos_pos.item.save();'},
            'delete':{'label':'Delete', 'fn':'M.ciniki_sapos_pos.item.remove();'},
            }},
        };
    this.item.liveSearchCb = function(s, i, v) {
        this.liveSearchRN++;
        var sN = this.liveSearchRN;
        if( i == 'code' || i == 'description' ) {
            M.api.getJSONBgCb('ciniki.sapos.invoiceItemSearch', {'tnid':M.curTenantID,
                'field':i, 'invoice_id':M.ciniki_sapos_pos.checkout.invoice_id, 'start_needle':v, 'limit':15}, function(rsp) {
                    if( sN == M.ciniki_sapos_pos.item.liveSearchRN ) {
                        M.ciniki_sapos_pos.item.liveSearchShow(s,i,M.gE(M.ciniki_sapos_pos.item.panelUID + '_' + i), rsp.items);
                    }
                });
        }
    }
    this.item.liveSearchResultClass = function(s,f,i,j,d) {
        if( d.item.price_description != null && d.item.price_description != '' 
            && this.sections[s].fields[f].livesearchcols-j == 1 ) {
            return 'multiline';
        }
        return 'multiline';
    }
    this.item.liveSearchResultValue = function(s,f,i,j,d) {
        if( j == 0 ) {
            var mt = d.item.description;
            if( (f == 'code' || f == 'description') && d.item != null ) { 
                if( d.item.code != null && d.item.code != '' ) {
                    mt = d.item.code + ' - ' + d.item.description;
                }
            }
            if( M.curTenant.sapos.settings != null && M.curTenant.sapos.settings['quote-notes-product-synopsis'] == 'yes' ) {
                d.item.notes = d.item.synopsis;
            }
            if( d.item.notes != '' ) {
                return '<span class="maintext">' + mt + '</span><span class="subtext">' + d.item.notes + '</span>';
            }
            return mt;
        }
        if( j == 1 && this.sections[s].fields[f].livesearchcols == 3 ) {
            if( d.item.available_display != null && d.item.available_display != '' ) { return d.item.available_display; }
            if( d.item.registrations_available != null ) { return d.item.registrations_available; }
            if( d.item.inventory_available != null ) { return d.item.inventory_available; }
        }
        if( this.sections[s].fields[f].livesearchcols-j == 1 ) {
            if( d.item.price_description != null && d.item.price_description != '' ) {
                return '<span class="maintext">' + d.item.unit_amount + '</span><span class="subtext">' + d.item.price_description + '</span>';
            }
            return d.item.unit_amount;
        }
        return '';
    }
    this.item.liveSearchResultRowFn = function(s,f,i,j,d) {
        if( (f == 'code' || f == 'description') && d.item != null ) {
            return 'M.ciniki_sapos_pos.item.updateFromSearch(\'' + s + '\',\'' + f + '\',\'' + d.item.object + '\',\'' + d.item.object_id + '\',\'' + escape(d.item.code!=null?d.item.code:'') + '\',\'' + escape(d.item.description) + '\',\'' + d.item.quantity + '\',\'' + escape(d.item.unit_amount) + '\',\'' + escape(d.item.unit_discount_amount) + '\',\'' + escape(d.item.unit_discount_percentage) + '\',\'' + d.item.taxtype_id + '\',\'' + d.item.price_id + '\',\'' + d.item.flags + '\',\'' + escape(d.item.notes) + '\');';
        }
    };

    this.item.updateFromSearch = function(s, fid, o, oid, c, d, q, u, uda, udp, t, pid, flags, n) {
        this.object = o;
        this.object_id = oid;
        if( this.sections.details.fields.code.active == 'yes' ) {
            this.setFieldValue('code', unescape(c));
        }
        this.setFieldValue('description', unescape(d));
        // Only update quantity if nothing specified yet
        if( this.formFieldValue(this.sections.details.fields.quantity, 'quantity') == '' ) {
            this.setFieldValue('quantity', q);
        }
        this.setFieldValue('unit_amount', unescape(u));
        this.setFieldValue('unit_discount_amount', unescape(uda));
        this.setFieldValue('unit_discount_percentage', unescape(udp));
        if( M.curTenant.modules['ciniki.taxes'] != null ) {
            this.setFieldValue('taxtype_id', t);
        }
        this.price_id = pid;
        if( M.modFlagOn('ciniki.sapos', 0x02000000) ) {
            this.setFieldValue('flags1', flags);
        }
        this.setFieldValue('notes', unescape(n));
        this.removeLiveSearch(s, fid);
        if( M.modFlagOn('ciniki.sapos', 0x08000000) && (flags&0x8000) == 0x8000 ) {
            this.sections.details.fields.donation_category.visible = 'yes';
            this.showHideFormField('details', 'donation_category');
        }
    };
    this.item.fieldValue = function(s, i, d) {
        if( this.data != null && this.data[i] != null ) { return this.data[i]; }
        return '';
    };
    this.item.fieldHistoryArgs = function(s, i) {
        return {'method':'ciniki.sapos.history', 'args':{'tnid':M.curTenantID,
            'object':'ciniki.sapos.invoice_item', 'object_id':this.item_id, 'field':i}};
    };
    this.item.open = function(cb, iid, inid) {
        if( iid != null ) { this.item_id = iid; }
        if( inid != null ) { this.invoice_id = inid; }
        if( this.item_id > 0 ) {
            this.sections._buttons.buttons.delete.visible = 'yes';
            M.api.getJSONCb('ciniki.sapos.invoiceItemGet', {'tnid':M.curTenantID, 'item_id':this.item_id, 'taxtypes':'yes'}, function(rsp) {
                if( rsp.stat != 'ok' ) {
                    M.api.err(rsp);
                    return false;
                }
                var p = M.ciniki_sapos_pos.item;
                p.data = rsp.item;
                p.price_id = rsp.item.price_id;
                p.object = rsp.item.object;
                p.object_id = rsp.item.object_id;
                p.sections.details.fields.flags1.on_fields = [];
                p.sections.details.fields.donation_category.visible = 'no';
                if( M.modFlagOn('ciniki.sapos', 0x08000000) ) {
                    p.sections.details.fields.flags1.on_fields = ['donation_category'];
                    if( (rsp.item.flags&0x8000) == 0x8000 ) {
                        p.sections.details.fields.donation_category.visible = 'yes';
                    }
                }
                p.refresh();
                p.show(cb);
            });
        } else {
            var p = M.ciniki_sapos_pos.item;
            p.reset();
            p.object = '';
            p.object_id = 0;
            p.price_id = 0;
            p.sections._buttons.buttons.delete.visible = 'no';
            p.sections.details.fields.flags1.on_fields = [];
            p.sections.details.fields.donation_category.visible = 'no';
            if( M.modFlagOn('ciniki.sapos', 0x08000000) ) {
                p.sections.details.fields.flags1.on_fields = ['donation_category'];
            }
            p.refresh();
            p.show(cb);
        }
    };

    this.item.save = function() {
        if( !this.checkForm() ) { return false; }
        if( this.item_id > 0 ) {
            var c = this.serializeForm('no');
            if( this.object != this.data.object ) {
                c += 'object=' + this.object + '&';
            }
            if( this.object_id != this.data.object_id ) {
                c += 'object_id=' + this.object_id + '&';
            }
            if( this.price_id != this.data.price_id ) {
                c += 'price_id=' + this.price_id + '&';
            }
            if( c != '' ) {
                M.api.postJSONCb('ciniki.sapos.invoiceItemUpdate', {'tnid':M.curTenantID, 'item_id':this.item_id}, c, function(rsp) {
                    if( rsp.stat != 'ok' ) {
                        M.api.err(rsp);
                        return false;
                    }
                    M.ciniki_sapos_pos.item.close();
                });
            } else {
                this.close();
            }
        } else {
            var c = this.serializeForm('yes');
            if( this.object != '' ) {
                c += 'object=' + this.object + '&';
            }
            if( this.object_id > 0 ) {
                c += 'object_id=' + this.object_id + '&';
            }
            if( this.price_id > 0 ) {
                c += 'price_id=' + this.price_id + '&';
            }
            M.api.postJSONCb('ciniki.sapos.posItemAdd', {'tnid':M.curTenantID, 'invoice_id':this.invoice_id}, c, function(rsp) {
                if( rsp.stat != 'ok' ) {
                    M.api.err(rsp);
                    return false;
                }
                var p = M.ciniki_sapos_pos.checkout;
                if( p.invoice_id == 0 ) {
                    p.invoice_id = rsp.invoice.id;
                }
                M.ciniki_sapos_pos.item.close();
            });
        }
    }
    this.item.remove = function() {
        if( this.item_id <= 0 ) { return false; }
        M.confirm("Are you sure you want to remove this item?",null,function() {
            M.api.getJSONCb('ciniki.sapos.invoiceItemDelete', {'tnid':M.curTenantID, 'item_id':M.ciniki_sapos_pos.item.item_id}, function(rsp) {
                if( rsp.stat != 'ok' ) {
                    M.api.err(rsp);
                    return false;
                }
                M.ciniki_sapos_pos.item.close();
            });
        });
    }
    this.item.addButton('save', 'Save', 'M.ciniki_sapos_pos.item.save();');
    this.item.addClose('Cancel');

    //
    // The transaction panel
    //
    this.transaction = new M.panel('Payment', 'ciniki_sapos_pos', 'transaction', 'mc', 'medium', 'sectioned', 'ciniki.sapos.pos.transaction');
    this.transaction.transaction_id = 0;
    this.transaction.data = {};
    this.transaction.sections = {
        'details':{'label':'Payment Type', 'fields':{
            'source':{'label':'', 'hidelabel':'yes', 'join':'no', 'type':'toggle', 'size':'7.5', 'required':'yes', 'toggles':{}},
            }},
        '_amount':{'label':'', 'fields':{
            'customer_amount':{'label':'Amount', 'type':'text', 'size':'small'},
            }},
        '_notes':{'label':'Notes', 'fields':{
            'notes':{'label':'', 'hidelabel':'yes', 'type':'textarea', 'size':'small'},
            }},
        '_buttons':{'label':'', 'buttons':{
            'save':{'label':'Save', 'fn':'M.ciniki_sapos_pos.transaction.save();'},
            'delete':{'label':'Delete', 
                'visible':function() { return M.ciniki_sapos_pos.transaction.transaction_id > 0 ? 'yes' : 'no';},
                'fn':'M.ciniki_sapos_pos.transaction.remove(M.ciniki_sapos_pos.transaction.transaction_id);',
                },
            }},
    }
    this.transaction.fieldValue = function(s, i, d) {
        if( this.data != null && this.data[i] != null ) { return this.data[i]; }
        return '';
    }
    this.transaction.fieldHistoryArgs = function(s, i) {
        return {'method':'ciniki.sapos.history', 'args':{'tnid':M.curTenantID, 'object':'ciniki.sapos.transaction', 'object_id':this.transaction_id, 'field':i}};
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
                var p = M.ciniki_sapos_pos.transaction;
                p.data = rsp.transaction;
                p.refresh();
                p.show(cb);
            });
        } else {
            var p = M.ciniki_sapos_pos.transaction;
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
            p.refresh();
            p.show(cb);
        }
    }
    this.transaction.save = function() {
        if( this.transaction_id > 0 ) {
            var c = this.serializeForm('no');
            if( c != '' ) {
                M.api.postJSONCb('ciniki.sapos.transactionUpdate', {'tnid':M.curTenantID, 'transaction_id':this.transaction_id}, c, function(rsp) {
                        if( rsp.stat != 'ok' ) {
                            M.api.err(rsp);
                            return false;
                        }
                        M.ciniki_sapos_pos.transaction.close();
                    });
            } else {
                this.close();
            }
        } else {
            var c = this.serializeForm('yes');
            M.api.postJSONCb('ciniki.sapos.transactionAdd', {'tnid':M.curTenantID, 'transaction_type':20, 'invoice_id':this.invoice_id}, c, function(rsp) {
                if( rsp.stat != 'ok' ) {
                    M.api.err(rsp);
                    return false;
                }
                M.ciniki_sapos_pos.transaction.close();
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
                M.ciniki_sapos_pos.transaction.close();
            });
        });
    }
    this.transaction.addButton('save', 'Save', 'M.ciniki_sapos_pos.saveTransaction();');
    this.transaction.addClose('Cancel');

    //
    // The email invoice panel
    //
    this.email = new M.panel('Email Invoice',
        'ciniki_sapos_pos', 'email',
        'mc', 'medium', 'sectioned', 'ciniki.sapos.pos.email');
    this.email.invoice_id = 0;
    this.email.data = {};
    this.email.sections = {
        '_subject':{'label':'', 'fields':{
            'subject':{'label':'Subject', 'type':'text', 'history':'no'},
            }},
        '_textmsg':{'label':'Message', 'fields':{
            'textmsg':{'label':'', 'hidelabel':'yes', 'type':'textarea', 'size':'large', 'history':'no'},
            }},
        '_buttons':{'label':'', 'buttons':{
            'send':{'label':'Send', 'fn':'M.ciniki_sapos_pos.email.send();'},
            }},
    };
    this.email.fieldValue = function(s, i, d) {
        return this.data[i];
    };
    this.email.open = function(cb, invoice) {
        this.invoice_id = invoice.id;
        this.data.subject = 'Invoice #' + invoice.invoice_number;
        if( M.curTenant.sapos.settings['invoice-email-message'] != null ) {
            this.data.textmsg = M.curTenant.sapos.settings['invoice-email-message'];
        } else {
            this.data.textmsg = 'Please find your receipt attached.';
        }
        if( invoice.invoice_type == 20 ) {
            this.data.subject = 'Shopping Cart #' + invoice.invoice_number;
            if( M.curTenant.sapos.settings['cart-email-message'] != null ) {
                this.data.textmsg = M.curTenant.sapos.settings['cart-email-message'];
            } 
        } else if( invoice.invoice_type == 30 ) {
            this.data.subject = 'Receipt #' + invoice.invoice_number;
            if( M.curTenant.sapos.settings['pos-email-message'] != null ) {
                this.data.textmsg = M.curTenant.sapos.settings['pos-email-message'];
            }
        } else if( invoice.invoice_type == 40 ) {
            this.data.subject = 'Order #' + invoice.invoice_number;
            if( M.curTenant.sapos.settings['order-email-message'] != null ) {
                this.data.textmsg = M.curTenant.sapos.settings['order-email-message'];
            } 
        } else if( invoice.invoice_type == 90 ) {
            this.data.subject = 'Quote #' + invoice.invoice_number;
            if( M.curTenant.sapos.settings['quote-email-message'] != null ) {
                this.data.textmsg = M.curTenant.sapos.settings['quote-email-message'];
            } 
        }
        this.refresh();
        this.show(cb);
    };
    this.email.send = function() {
        var subject = this.formFieldValue(this.sections._subject.fields.subject, 'subject');
        var textmsg = this.formFieldValue(this.sections._textmsg.fields.textmsg, 'textmsg');
        M.api.getJSONCb('ciniki.sapos.invoicePDF', {'tnid':M.curTenantID, 
            'invoice_id':this.invoice_id, 'subject':subject, 'textmsg':textmsg, 'output':'pdf', 'email':'yes'}, function(rsp) {
                if( rsp.stat != 'ok' ) {
                    M.api.err(rsp);
                    return false;
                }
                M.ciniki_sapos_pos.email.close();
            });
    };
    this.email.addClose('Cancel');

    //
    // The search results panel when multiple items found
    //
    this.searchresults = new M.panel('Items Found',
        'ciniki_sapos_pos', 'searchresults',
        'mc', 'medium', 'sectioned', 'ciniki.sapos.pos.searchresults');
    this.searchresults.invoice_id = 0;
    this.searchresults.data = {};
    this.searchresults.sections = {
        'items':{'label':'Items Found', 'type':'simplegrid', 'num_cols':4,
            'headerValues':['Code', 'Description', 'Price', ''],
            'cellClasses':['', 'multiline', 'alignright', 'alignright'],
            },
    }
    this.searchresults.cellValue = function(s, i, j, d) {
        switch(j) {
            case 0: return d.item.code;
            case 1: 
                if( d.item.notes != null && d.item.notes != '' ) {
                    return '<span class="maintext">' + d.item.description + '</span><span class="subtext">' + d.item.notes.replace(/\n/g, '<br/>') + '</span>';
                }
                return d.item.description;
            case 2: return d.item.unit_amount;
            case 3: return '<button onclick="M.ciniki_sapos_pos.checkout.addItem(event,\'' + d.item.object + '\',\'' + d.item.object_id + '\');">Add</button>';
        }
    }
    this.searchresults.open = function(cb, v) {
        M.api.getJSONCb('ciniki.sapos.invoiceItemSearch', {'tnid':M.curTenantID, 'invoice_id':this.invoice_id, 'start_needle':v}, function(rsp) {
            if( rsp.stat != 'ok' ) {
                M.api.err(rsp);
                return false;
            }
            if( rsp.items != null && rsp.items.length == 1 ) {
                M.ciniki_sapos_pos.checkout.addItem(null,rsp.items[0].item.object, rsp.items[0].item.object_id);
                return;
            }
            var p = M.ciniki_sapos_pos.searchresults;
            p.data = rsp;
            p.refresh();
            p.show(cb);
        });
    }
    this.searchresults.addClose('Back');

    //
    // Start the app
    //
    this.start = function(cb, aP, aG) {
        args = {};
        if( aG != null ) { args = eval(aG); }
        var aC = M.createContainer(aP, 'ciniki_sapos_pos', 'yes');
        if( aC == null ) {
            M.alert('App Error');
            return false;
        }

        //
        // Setup the taxtypes available for the tenant
        //
        if( M.curTenant.modules['ciniki.taxes'] != null ) {
            this.item.sections.details.fields.taxtype_id.active = 'yes';
            this.item.sections.details.fields.taxtype_id.options = {'0':'No Taxes'};
            if( M.curTenant.taxes != null && M.curTenant.taxes.settings.types != null ) {
                for(i in M.curTenant.taxes.settings.types) {
                    this.item.sections.details.fields.taxtype_id.options[M.curTenant.taxes.settings.types[i].type.id] = M.curTenant.taxes.settings.types[i].type.name;
                }
            }
            //
            // Setup the tax locations
            //
            if( (M.curTenant.modules['ciniki.taxes'].flags&0x01) > 0 
                && M.curTenant.taxes.settings != null
                && M.curTenant.taxes.settings.locations != null
                ) {
                var locations = {'0':'Use Shipping Address'};
                var locs = M.curTenant.taxes.settings.locations;
                for(i in locs) {
                    locations[locs[i].location.id] = locs[i].location.name + ' [' + (locs[i].location.rates!=null?locs[i].location.rates:'None') + ']';
                }
            }
        } else {
            this.item.sections.details.fields.taxtype_id.active = 'no';
            this.item.sections.details.fields.taxtype_id.options = {'0':'No Taxes'};
        }
        
        //
        // If instore pickups, then make sure they are visible
        //
        if( M.modFlagOn('ciniki.sapos', 0x20000000) ) {
            this.menu.size = 'large mediumaside';
        } else {
            this.menu.size = 'large';
        }

        //
        // Setup list of sources for payments
        //
        // FIXME: This needs to be changed to settings so list can be updated by tenant
        this.transactionSources = {
//            '20':'Square',
            '80':'Credit Card',
            '90':'Debit Card',
            '100':'Cash',
            '105':'Check',
            '110':'Email Transfer',
            '115':'Gift Certificate',
            '120':'Other',
        }
        if( M.modFlagOn('ciniki.sapos', 0x200000) ) {
            this.transactionSources[10] = 'Paypal';
        } else if( this.transactionSources[10] != null ) {
            delete(this.transactionSources[10]);
        }
        this.transaction.sections.details.fields.source.toggles = this.transactionSources;

        this.menu.open(cb,0);
    };
}
