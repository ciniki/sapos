//
// This panel will create or edit an invoice
//
function ciniki_sapos_shipment() {
	this.shipmentStatus = {
		'10':'Packing',
		'20':'Packed',
		'30':'Shipped',
		'40':'Received',
		};
	this.weightUnits = {
		'10':'lb',
		'20':'kg',
		};
	this.init = function() {
		//
		// The edit invoice panel
		//
		this.edit = new M.panel('Shipment',
			'ciniki_sapos_shipment', 'edit',
			'mc', 'medium mediumaside', 'sectioned', 'ciniki.sapos.shipment.edit');
		this.edit.shipment_id = 0;
		this.edit.invoice_id = 0;
		this.edit.data = {};
		this.edit.sections = {
			'details':{'label':'', 'aside':'yes', 'fields':{
				'shipment_number':{'label':'Number', 'type':'text', 'size':'small'},
				'status':{'label':'Status', 'type':'select', 'options':M.ciniki_sapos_shipment.shipmentStatus},
				'weight':{'label':'Weight', 'active':'yes', 'type':'text', 'size':'small'},
				'weight_units':{'label':'Units', 'type':'toggle', 'default':'10', 'toggles':this.weightUnits},
				'shipping_company':{'label':'Shipper', 'type':'text', 'size':'medium'},
				'tracking_number':{'label':'Tracking #', 'type':'text'},
				'td_number':{'label':'TD #', 'type':'text'},
				'boxes':{'label':'Boxes', 'type':'text', 'size':'small'},
				'pack_date':{'label':'Pack Date', 'type':'date', 'size':'medium'},
				'ship_date':{'label':'Ship Date', 'type':'date', 'size':'medium'},
				}},
			'invoice_items':{'label':'Unshipped Items', 'type':'simplegrid', 'num_cols':4,
				'headerValues':['Item', 'Qty', 'Inv', ''],
				'noData':'All items shipped',
				},
			'items':{'label':'Shipment Items', 'type':'simplegrid', 'num_cols':2,
				'headerValues':['Item', 'Qty'],
				},
			'_buttons':{'label':'', 'buttons':{
				'save':{'label':'Save', 'fn':'M.ciniki_sapos_shipment.saveShipment();'},
				'print':{'label':'Print', 'fn':'M.ciniki_sapos_shipment.printPackingSlip(M.ciniki_sapos_shipment.edit.shipment_id);'},
				'delete':{'label':'Delete', 'fn':'M.ciniki_sapos_shipment.deleteShipment();'},
				}},
		};
		this.edit.fieldValue = function(s, i, d) {
			return this.data[i];
		};
		this.edit.fieldHistoryArgs = function(s, i) {
			return {'method':'ciniki.sapos.history', 'args':{'business_id':M.curBusinessID,
				'object':'ciniki.sapos.shipment', 'object_id':this.shipment_id, 'field':i}};
		};
		this.edit.sectionData = function(s) { if( this.data[s] != null ) { return this.data[s]; }};
		this.edit.cellValue = function(s,i,j,d) {
			if( s == 'invoice_items' ) {
				switch(j) {
					case 0: return d.item.description;
					case 1: return d.item.required_quantity;
					case 2: return d.item.inventory_quantity;
					case 3: 
						var s = '';
						if( d.item.inventory_quantity > 0 ) {
							s = "<button onclick=\"event.stopPropagation(); M.ciniki_sapos_shipment.addShipmentItem(\'" + d.item.id + "\',\'" + (d.item.required_quantity<=d.item.inventory_quantity?d.item.required_quantity:d.item.inventory_quantity) + "\'); return false;\">Add</button>";
						}
						return s;
				}
			}
			if( s == 'items' ) {
				switch(j) {
					case 0: return d.item.description;
					case 1: return d.item.quantity;
				}
			}
		};
		this.edit.rowFn = function(s, i, d) {
			if( s == 'invoice_items' ) {
				if( d.item.object == 'ciniki.products.product' ) {
					return 'M.startApp(\'ciniki.products.inventory\',null,\'M.ciniki_sapos_shipment.showShipment();\',\'mc\',{\'product_id\':\'' + d.item.object_id + '\'});';
				}
			}
			if( s == 'items' && M.ciniki_sapos_shipment.edit.data.status < 30 ) {
				return 'M.ciniki_sapos_shipment.editItem(\'M.ciniki_sapos_shipment.showShipment();\',\'' + d.item.id + '\');';
			}
			return '';
		};
		this.edit.addButton('save', 'Save', 'M.ciniki_sapos_shipment.saveShipment();');
		this.edit.addClose('Cancel');

		//
		// The item add/edit panel
		//
		this.item = new M.panel('Shipment Item',
			'ciniki_sapos_shipment', 'item',
			'mc', 'medium', 'sectioned', 'ciniki.sapos.shipment.item');
		this.item.sitem_id = 0;
		this.item.data = {};
		this.item.sections = {
			'details':{'label':'', 'type':'simplegrid', 'num_cols':1},
			'_quantity':{'label':'', 'fields':{
				'quantity':{'label':'Quantity', 'type':'text', 'size':'small'},
				}},
			'_notes':{'label':'Notes', 'fields':{
				'notes':{'label':'', 'hidelabel':'yes', 'type':'textarea', 'size':'small'},
				}},
			'_buttons':{'label':'', 'buttons':{
				'save':{'label':'Save', 'fn':'M.ciniki_sapos_shipment.saveItem();'},
				'delete':{'label':'Delete', 'fn':'M.ciniki_sapos_shipment.deleteItem(M.ciniki_sapos_shipment.item.item_id);'},
				}},
			};
		this.item.fieldValue = function(s, i, d) {
			if( this.data != null && this.data[i] != null ) { return this.data[i]; }
			return '';
		};
		this.item.fieldHistoryArgs = function(s, i) {
			return {'method':'ciniki.sapos.history', 'args':{'business_id':M.curBusinessID,
				'object':'ciniki.sapos.shipment_item', 'object_id':this.item_id, 'field':i}};
		};
		this.item.addButton('save', 'Save', 'M.ciniki_sapos_shipment.saveItem();');
		this.item.addClose('Cancel');
	}; 

	this.start = function(cb, aP, aG) {
		args = {};
		if( aG != null ) { args = eval(aG); }
		var aC = M.createContainer(aP, 'ciniki_sapos_shipment', 'yes');
		if( aC == null ) {
			alert('App Error');
			return false;
		}

		if( args.shipment_id != null && args.invoice_id != null ) {
			this.showShipment(cb, args.shipment_id, args.invoice_id, args.shipment_number);
		} else if( args.invoice_id != null ) {
			this.showShipment(cb, 0, args.invoice_id, args.shipment_number);
		} else {
			this.showShipment(cb, args.shipment_id);
		}
	};

	this.showShipment = function(cb, sid, iid, snum) {
		if( sid != null ) { this.edit.shipment_id = sid; }
		if( iid != null ) { this.edit.invoice_id = iid; }
		if( this.edit.shipment_id > 0 ) {
			this.edit.sections._buttons.buttons.delete.visible = 'yes';
			this.edit.sections._buttons.buttons.print.visible = 'yes';
			M.api.getJSONCb('ciniki.sapos.shipmentGet', {'business_id':M.curBusinessID,
				'shipment_id':this.edit.shipment_id}, function(rsp) {
					if( rsp.stat != 'ok' ) {
						M.api.err(rsp);
						return false;
					}
					var p = M.ciniki_sapos_shipment.edit;
					p.data = rsp.shipment;
					// Only show the delete button when the shipment is still in packing.  If not 
					// the status must be reset to packing before it can be deleted.
					if( rsp.shipment.status >= 20 ) {
						p.sections._buttons.buttons.delete.visible = 'no';
					}
					if( rsp.shipment.invoice_items == null ) {
						p.data.invoice_items = {};
					}
					for(i in p.data.invoice_items) {
						if( p.data.invoice_items[i].item.required_quantity <= 0 ) {
							delete(p.data.invoice_items[i]);
						}
					}
					if( rsp.shipment.items == null ) {
						p.data.items = {};
					}
					p.refresh();
					p.show(cb);
				});
		} else if( this.edit.invoice_id > 0 ) {
			this.edit.sections._buttons.buttons.delete.visible = 'no';
			this.edit.sections._buttons.buttons.print.visible = 'no';
			M.api.getJSONCb('ciniki.sapos.invoiceGet', {'business_id':M.curBusinessID,
				'invoice_id':this.edit.invoice_id, 'inventory':'yes'}, function(rsp) {
					if( rsp.stat != 'ok' ) {
						M.api.err(rsp);
						return false;
					}
					var p = M.ciniki_sapos_shipment.edit;
					p.data = {'status':'10',
						'shipment_number':(snum!=null?snum:'1'),
						'weight':'',
						'weight_units':'10',
						'shipping_company':'',
						'tracking_number':'',
						'boxes':'1',
						'pack_date':M.dateFormat(new Date()),
						'ship_date':'',
						'invoice_items':[],
						'items':[],
						};
					if( M.curBusiness.sapos.settings['shipments-default-shipper'] != null ) {
						p.data['shipping_company'] = M.curBusiness.sapos.settings['shipments-default-shipper'];
					}
					if( M.curBusiness.sapos.settings['shipments-default-weight-units'] != null ) {
						p.data['weight_units'] = M.curBusiness.sapos.settings['shipments-default-weight-units'];
					}
					if( rsp.invoice.items != null ) {
						for(i in rsp.invoice.items) {
							if( rsp.invoice.items[i].item.shipped_quantity < rsp.invoice.items[i].item.quantity ) {
								p.data.invoice_items.push(rsp.invoice.items[i]);
							}
						}
					}
					p.refresh();
					p.show(cb);
				});
		}
	};

	this.saveShipment = function() {
		if( this.edit.shipment_id > 0 ) {
			var c = this.edit.serializeForm('no');
			if( c != '' ) {
				M.api.postJSONCb('ciniki.sapos.shipmentUpdate', {'business_id':M.curBusinessID,
					'shipment_id':this.edit.shipment_id}, c, function(rsp) {
						if( rsp.stat != 'ok' ) {
							M.api.err(rsp);
							return false;
						}
						M.ciniki_sapos_shipment.edit.close();
					});	
			} else {
				this.edit.close();
			}
		} else {
			var c = this.edit.serializeForm('yes');
			M.api.postJSONCb('ciniki.sapos.shipmentAdd', {'business_id':M.curBusinessID,
				'invoice_id':this.edit.invoice_id}, c, function(rsp) {
					if( rsp.stat != 'ok' ) {
						M.api.err(rsp);
						return false;
					}
					M.ciniki_sapos_shipment.edit.close();
				});
		}
	};

	this.addShipmentItem = function(item_id, quantity) {
		if( this.edit.shipment_id > 0 ) {
			var c = '&item_id=' + item_id + '&quantity=' + quantity;
			M.api.postJSONCb('ciniki.sapos.shipmentItemAdd', {'business_id':M.curBusinessID,
				'shipment_id':this.edit.shipment_id}, c, function(rsp) {
					if( rsp.stat != 'ok' ) {
						M.api.err(rsp);
						return false;
					}
					M.ciniki_sapos_shipment.showShipment();
				});
		} else {
			var c = this.edit.serializeForm('yes');
			M.api.postJSONCb('ciniki.sapos.shipmentAdd', {'business_id':M.curBusinessID,
				'invoice_id':this.edit.invoice_id}, c, function(rsp) {
					if( rsp.stat != 'ok' ) {
						M.api.err(rsp);
						return false;
					}
					M.ciniki_sapos_shipment.edit.shipment_id = rsp.shipment.id;
					var c = '&item_id=' + item_id + '&quantity=' + quantity;
					M.api.postJSONCb('ciniki.sapos.shipmentItemAdd', {'business_id':M.curBusinessID,
						'shipment_id':rsp.shipment.id}, c, function(rsp) {
							if( rsp.stat != 'ok' ) {
								M.api.err(rsp);
								return false;
							}
							M.ciniki_sapos_shipment.showShipment();
						});
				});
		}
	};

	this.deleteShipment = function() {
		if( this.edit.shipment_id <= 0 ) { return false; }
		if( confirm("Are you sure you want to remove this shipment.  All items in the shipment will be returned to inventory?") ) {
			M.api.getJSONCb('ciniki.sapos.shipmentDelete', {'business_id':M.curBusinessID,
				'shipment_id':this.edit.shipment_id}, function(rsp) {
					if( rsp.stat != 'ok' ) {
						M.api.err(rsp);
						return false;
					}
					M.ciniki_sapos_shipment.edit.close();
				});
		}
	};

	this.printPackingSlip = function(sid) {
		if( this.edit.shipment_id > 0 ) {
			var c = this.edit.serializeForm('no');
			if( c != '' ) {
				M.api.postJSONCb('ciniki.sapos.shipmentUpdate', {'business_id':M.curBusinessID,
					'shipment_id':this.edit.shipment_id}, c, function(rsp) {
						if( rsp.stat != 'ok' ) {
							M.api.err(rsp);
							return false;
						}
						M.ciniki_sapos_shipment.showShipment();
						window.open(M.api.getUploadURL('ciniki.sapos.packingSlipPDF',
							{'business_id':M.curBusinessID, 'shipment_id':sid}));
					});	
			} else {
				window.open(M.api.getUploadURL('ciniki.sapos.packingSlipPDF',
					{'business_id':M.curBusinessID, 'shipment_id':sid}));
			}
		}
	};

	this.editItem = function(cb, iid, sid) {
		if( iid != null ) { this.item.item_id = iid; }
		if( sid != null ) { this.item.shipment_id = sid; }
		if( this.item.item_id > 0 ) {
			this.item.sections._buttons.buttons.delete.visible = 'yes';
			M.api.getJSONCb('ciniki.sapos.shipmentItemGet', {'business_id':M.curBusinessID,
				'sitem_id':this.item.item_id}, function(rsp) {
					if( rsp.stat != 'ok' ) {
						M.api.err(rsp);
						return false;
					}
					var p = M.ciniki_sapos_shipment.item;
					p.data = rsp.item;
					p.refresh();
					p.show(cb);
				});
		}
	};

	this.saveItem = function() {
		if( this.item.item_id > 0 ) {
			var c = this.item.serializeForm('no');
			if( c != '' ) {
				M.api.postJSONCb('ciniki.sapos.shipmentItemUpdate', {'business_id':M.curBusinessID,
					'sitem_id':this.item.item_id}, c, function(rsp) {
						if( rsp.stat != 'ok' ) {
							M.api.err(rsp);
							return false;
						}
						M.ciniki_sapos_shipment.item.close();
					});
			} else {
				this.item.close();
			}
		} else {
			var c = this.item.serializeForm('yes');
			M.api.postJSONCb('ciniki.sapos.shipmentItemAdd', {'business_id':M.curBusinessID,
				'shipment_id':this.item.shipment_id}, c, function(rsp) {
					if( rsp.stat != 'ok' ) {
						M.api.err(rsp);
						return false;
					}
					M.ciniki_sapos_shipment.item.close();
				});
		}
	};

	this.deleteItem = function(iid) {
		if( iid <= 0 ) { return false; }
		if( confirm("Are you sure you want to remove this item from the shipment?") ) {
			M.api.getJSONCb('ciniki.sapos.shipmentItemDelete', {'business_id':M.curBusinessID,
				'sitem_id':iid}, function(rsp) {
					if( rsp.stat != 'ok' ) {
						M.api.err(rsp);
						return false;
					}
					M.ciniki_sapos_shipment.item.close();
				});
		}
	};
}
