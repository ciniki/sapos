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
	this.shipmentFlags = {
		'1':{'name':'TD'},
		};
	this.weightUnits = {
		'10':'lb',
		'20':'kg',
		};
	this.colours = {};
	this.init = function() {
		//
		// The shipment panel
		//
		this.shipment = new M.panel('Shipment',
			'ciniki_sapos_shipment', 'shipment',
			'mc', 'medium mediumaside', 'sectioned', 'ciniki.sapos.shipment.shipment');
		this.shipment.shipment_id = 0;
		this.shipment.data = {};
		this.shipment.sections = {
			'info':{'label':'', 'aside':'yes', 'list':{
				'shipment_number':{'label':'Number'},
				'status':{'label':'Status'},
				'shipping_company':{'label':'Shipper'},
				'tracking_number':{'label':'Tracking #'},
//				'td_number':{'label':'TD #'},
				'boxes':{'label':'Boxes'},
				'dimensions':{'label':'Dimensions'},
				'pack_date':{'label':'Pack Date'},
				'ship_date':{'label':'Ship Date'},
				}},
			'items':{'label':'Shipped Items', 'type':'simplegrid', 'num_cols':2,
				'headerValues':['Item', 'Qty'],
				},
		};
		this.shipment.sectionData = function(s) { 
			if( s == 'info' ) { return this.sections[s].list; }
			if( this.data[s] != null ) { return this.data[s]; }
		};
		this.shipment.listLabel = function(s, i, d) {
			return d.label;
		};
		this.shipment.listValue = function(s, i, d) {
			return this.data[i];
		};
		this.shipment.cellValue = function(s,i,j,d) {
			if( s == 'items' ) {
				switch(j) {
					case 0: return d.item.description;
					case 1: return d.item.quantity;
				}
			}
		};
		this.shipment.addClose('Back');
		
		//
		// The edit shipment panel
		//
		this.edit = new M.panel('Shipment',
			'ciniki_sapos_shipment', 'edit',
			'mc', 'medium mediumaside', 'sectioned', 'ciniki.sapos.shipment.edit');
		this.edit.shipment_id = 0;
		this.edit.invoice_id = 0;
		this.edit.data = {};
		this.edit.sections = {
			'invoice':{'label':'', 'aside':'yes', 'active':'yes', 'list':{
				'invoice_number':{'label':'Invoice #'},
				'invoice_po_number':{'label':'PO #', 'visible':'no'},
				'customer_name':{'label':'Customer'},
				'shipping_address':{'label':'Ship To', 'visible':'no'},
				'submitted_by':{'label':'Submitted By', 'visible':'no'},
				}},
			'info':{'label':'', 'aside':'yes', 'active':'no', 'list':{
				'shipment_number':{'label':'Number'},
				'status_text':{'label':'Status'},
				'flags_text':{'label':'Options'},
				'weight':{'label':'Weight'},
				'shipping_company':{'label':'Shipper'},
				'tracking_number':{'label':'Tracking #'},
//				'td_number':{'label':'TD #'},
				'boxes':{'label':'Boxes'},
				'dimensions':{'label':'Boxes'},
				'pack_date':{'label':'Pack Date'},
				'ship_date':{'label':'Ship Date'},
				'freight_amount':{'label':'Freight Amount'},
				}},
			'details':{'label':'', 'aside':'yes', 'active':'yes', 'fields':{
				'shipment_number':{'label':'Number', 'type':'text', 'size':'small'},
				'status':{'label':'Status', 'type':'toggle', 'toggles':M.ciniki_sapos_shipment.shipmentStatus},
				'flags':{'label':'Options', 'type':'flags', 'flags':M.ciniki_sapos_shipment.shipmentFlags},
				'weight':{'label':'Weight', 'active':'yes', 'type':'text', 'size':'small'},
				'weight_units':{'label':'Units', 'type':'toggle', 'default':'10', 'toggles':this.weightUnits},
				'shipping_company':{'label':'Shipper', 'type':'text', 'size':'medium'},
				'tracking_number':{'label':'Tracking #', 'type':'text'},
				'td_number':{'label':'TD #', 'type':'text'},
				'boxes':{'label':'Boxes', 'type':'text', 'size':'small'},
				'dimensions':{'label':'Dimensions', 'type':'text'},
				'pack_date':{'label':'Pack Date', 'type':'date', 'size':'medium'},
				'ship_date':{'label':'Ship Date', 'type':'date', 'size':'medium'},
				'freight_amount':{'label':'Freight Amount', 'type':'text', 'size':'small'},
				}},
			'_notes':{'label':'Notes', 'aside':'yes', 'fields':{
				'notes':{'label':'', 'hidelabel':'yes', 'type':'textarea', 'size':'small'},
				}},
			'_customer_notes':{'label':'Customer Notes', 'aside':'yes', 'fields':{
				'customer_notes':{'label':'', 'hidelabel':'yes', 'type':'textarea', 'size':'small'},
				}},
			'invoice_items':{'label':'Unshipped Items', 'type':'simplegrid', 'num_cols':4,
				'headerValues':['Item', 'Qty', 'Inv', ''],
				'cellClasses':['multiline','','','',''],
				'noData':'All items shipped',
				},
			'items':{'label':'Shipment Items', 'type':'simplegrid', 'num_cols':2,
				'headerValues':['Item', 'Qty'],
				'cellClasses':['multiline', ''],
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
			if( i == 'customer_notes' ) {
				return {'method':'ciniki.sapos.history', 'args':{'business_id':M.curBusinessID,
					'object':'ciniki.sapos.invoice', 'object_id':this.invoice_id, 'field':i}};
			} 
			return {'method':'ciniki.sapos.history', 'args':{'business_id':M.curBusinessID,
				'object':'ciniki.sapos.shipment', 'object_id':this.shipment_id, 'field':i}};
		};
		this.edit.sectionData = function(s) { 
			if( s == 'invoice' ) { return this.sections[s].list; }
			if( s == 'info' ) { return this.sections[s].list; }
			if( this.data[s] != null ) { return this.data[s]; }
		};
		this.edit.listLabel = function(s, i, d) {
			return d.label;
		};
		this.edit.listValue = function(s, i, d) {
			if( i == 'shipping_address' ) { return this.data[i].replace(/\n/g, '<br/>') + (this.data.shipping_phone!=null&&this.data.shipping_phone!=''?'<br/> Phone: ' + this.data.shipping_phone:''); }
			if( i == 'invoice_number' ) { return this.data[i] + ' <span class="subdue">[' + this.data['invoice_status_text'] + ']</span>'; }
			if( i == 'weight' ) { return this.data[i] + ' ' + this.data['weight_units_text']; }
			return this.data[i];
		};
		this.edit.cellValue = function(s,i,j,d) {
			if( s == 'invoice_items' ) {
				switch(j) {
					case 0:
						if( d.item.code != null && d.item.code != '' ) {
							return '<span class="maintext">' + d.item.code + '</span><span class="subtext">' + d.item.description + '</span>' + (d.item.notes!=null&&d.item.notes!=''?'<span class="subsubtext">'+d.item.notes+'</span>':'');
						}
						if( d.item.notes != null && d.item.notes != '' ) {
							return '<span class="maintext">' + d.item.description + '</span><span class="subtext">' + d.item.notes + '</span>';
						}
						return d.item.description;
					case 1: return d.item.required_quantity;
					case 2: return d.item.inventory_quantity + (d.item.inventory_reserved!=null?' ['+d.item.inventory_reserved+']':'');
					case 3: 
						var s = '';
						if( d.item.inventory_quantity > 0 && this.data['status'] < 30 ) {
							s = "<button onclick=\"event.stopPropagation(); M.ciniki_sapos_shipment.addShipmentItem(\'" + d.item.id + "\',\'" + (d.item.required_quantity<=d.item.inventory_quantity?d.item.required_quantity:d.item.inventory_quantity) + "\'); return false;\">Add</button>";
						}
						s += "&nbsp;<button onclick=\"event.stopPropagation(); M.startApp(\'ciniki.products.inventory\',null,\'M.ciniki_sapos_shipment.updateEditItems();\',\'mc\',{\'product_id\':\'" + d.item.object_id + "\'}); return false;\">Inv</button>";
						return s;
				}
			}
			if( s == 'items' ) {
				switch(j) {
					case 0:
						if( d.item.code != null && d.item.code != '' ) {
							return '<span class="maintext">' + d.item.code + '</span><span class="subtext">' + d.item.description + '</span>' + (d.item.notes!=null&&d.item.notes!=''?'<span class="subsubtext">'+d.item.notes+'</span>':'');
						}
						if( d.item.notes != null && d.item.notes != '' ) {
							return '<span class="maintext">' + d.item.description + '</span><span class="subtext">' + d.item.notes + '</span>';
						}
						return d.item.description;
					case 1: return d.item.quantity;
				}
			}
		};
		this.edit.rowFn = function(s, i, d) {
			if( s == 'invoice_items' ) {
				if( d.item.object == 'ciniki.products.product' ) {
					return 'M.startApp(\'ciniki.sapos.invoice\',null,\'M.ciniki_sapos_shipment.updateEditItems();\',\'mc\',{\'invoice_id\':M.ciniki_sapos_shipment.edit.invoice_id,\'item_id\':\'' + d.item.id + '\'});';
//					return 'M.startApp(\'ciniki.products.inventory\',null,\'M.ciniki_sapos_shipment.showEdit();\',\'mc\',{\'product_id\':\'' + d.item.object_id + '\'});';
				}
			}
			if( s == 'items' && M.ciniki_sapos_shipment.edit.data.status < 30 ) {
				return 'M.ciniki_sapos_shipment.editItem(\'M.ciniki_sapos_shipment.showEdit();\',\'' + d.item.id + '\');';
			}
			return '';
		};
		this.edit.rowStyle = function(s, i, d) {
			if( s == 'invoice_items' ) {
				if( (d.item.flags&0x0300) > 0 ) {
					return 'background: ' + M.ciniki_sapos_shipment.colours['invoice-item-forced-backordered'] + ';';
				} else if( d.item.required_quantity != null && d.item.required_quantity == 0 ) {
					return 'background: ' + M.ciniki_sapos_shipment.colours['invoice-item-fulfilled'] + ';';
				} else if( d.item.required_quantity > 0 && d.item.inventory_quantity > 0 && d.item.inventory_quantity < d.item.required_quantity) {
					return 'background: ' + M.ciniki_sapos_shipment.colours['invoice-item-partial'] + ';';
				} else if( d.item.required_quantity > 0 && d.item.inventory_quantity > 0 ) {
					return 'background: ' + M.ciniki_sapos_shipment.colours['invoice-item-available'] + ';';
				} else if( d.item.required_quantity > 0 && d.item.inventory_quantity == 0 ) {
					return 'background: ' + M.ciniki_sapos_shipment.colours['invoice-item-backordered'] + ';';
				}
			}
			return '';
		};
		this.edit.noData = function(s) {
			if( this.sections[s].noData != null ) {
				return this.sections[s].noData;
			}
		};
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

		// Change also in invoice.js
		this.colours = {
			'invoice-item-available':'#C0FFC0',
			'invoice-item-partial':'#FFFFC0',
			'invoice-item-backordered':'#FFC6C6',
			'invoice-item-forced-backordered':'#FFC6C6',
			'invoice-item-fulfilled':'#E6E6E6',
		};
		for(i in this.colours) {
			if( M.curBusiness.sapos.settings['ui-colours-' + i] != null ) {
				this.colours[i] = M.curBusiness.sapos.settings['ui-colours-' + i];
			}
		}
		//
		// Check if weight units should be hidden
		//
		if( M.curBusiness.sapos.settings['shipments-hide-weight-units'] != null && M.curBusiness.sapos.settings['shipments-hide-weight-units'] == 'yes' ) {
			this.edit.sections.details.fields.weight_units.visible = 'no';
		} else {
			this.edit.sections.details.fields.weight_units.visible = 'yes';
		}
		//
		// Check what the user should see
		//
		if( M.curBusiness.permissions.owners == null 
			&& M.curBusiness.permissions.employees == null 
			&& M.curBusiness.permissions.salesreps != null 
			&& (M.userPerms&0x01) == 0
			) {
			this.showShipment(cb, args.shipment_id);
		} else {
			if( args.shipment_id != null && args.invoice_id != null ) {
				this.showEdit(cb, args.shipment_id, args.invoice_id, args.shipment_number);
			} else if( args.invoice_id != null ) {
				this.showEdit(cb, 0, args.invoice_id, args.shipment_number);
			} else {
				this.showEdit(cb, args.shipment_id);
			}
		}
	};

	this.showShipment = function(cb, sid) {
		if( sid != null ) { this.shipment.shipment_id = sid; }
		if( this.shipment.shipment_id > 0 ) {
			M.api.getJSONCb('ciniki.sapos.shipmentGet', {'business_id':M.curBusinessID,
				'shipment_id':this.shipment.shipment_id}, function(rsp) {
					if( rsp.stat != 'ok' ) {
						M.api.err(rsp);
						return false;
					}
					var p = M.ciniki_sapos_shipment.shipment;
					p.data = rsp.shipment;
					p.refresh();
					p.show(cb);
				});
		}
	}

	this.showEdit = function(cb, sid, iid, snum) {
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
					if( rsp.shipment.invoice_po_number != null && rsp.shipment.invoice_po_number != '' ) {
						p.sections.invoice.list.invoice_po_number.visible = 'yes';
					}
					if( rsp.shipment.shipping_address != null && rsp.shipment.shipping_address != '' ) {
						p.sections.invoice.list.shipping_address.visible = 'yes';
					} else {
						p.sections.invoice.list.shipping_address.visible = 'no';
					}
					if( rsp.shipment.submitted_by != null && rsp.shipment.submitted_by != '' ) {
						p.sections.invoice.list.submitted_by.visible = 'yes';
					} else {
						p.sections.invoice.list.submitted_by.visible = 'no';
					}
					p.invoice_id = rsp.shipment.invoice_id;
					p.leftbuttons = {};
					p.rightbuttons = {};
					if( rsp.shipment.status >= 30 ) {
						p.sections.info.active = 'yes';
						p.sections.details.active = 'no';
						p.addClose('Back');
						p.sections._buttons.buttons.save.visible = 'no';
					} else {
						p.sections.info.active = 'no';
						p.sections.details.active = 'yes';
						p.addButton('save', 'Save', 'M.ciniki_sapos_shipment.saveShipment();');
						p.addClose('Cancel');
						p.sections._buttons.buttons.save.visible = 'yes';
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
			this.edit.sections._buttons.buttons.save.visible = 'yes';
			this.edit.sections._buttons.buttons.delete.visible = 'no';
			this.edit.sections._buttons.buttons.print.visible = 'no';
			this.edit.leftbuttons = {};
			this.edit.rightbuttons = {};
			this.edit.addButton('save', 'Save', 'M.ciniki_sapos_shipment.saveShipment();');
			this.edit.addClose('Cancel');
			this.edit.sections.info.active = 'no';
			this.edit.sections.details.active = 'yes';
			M.api.getJSONCb('ciniki.sapos.invoiceGet', {'business_id':M.curBusinessID,
				'invoice_id':this.edit.invoice_id, 'inventory':'yes'}, function(rsp) {
					if( rsp.stat != 'ok' ) {
						M.api.err(rsp);
						return false;
					}
					var p = M.ciniki_sapos_shipment.edit;
					p.data = {'status':'10',
						'invoice_number':(rsp.invoice.invoice_number!=null?rsp.invoice.invoice_number:''),
						'invoice_status_text':(rsp.invoice.status_text!=null?rsp.invoice.status_text:''),
						'invoice_po_number':(rsp.invoice.po_number!=null?rsp.invoice.po_number:''),
						'customer_name':(rsp.invoice.customer.display_name!=null?rsp.invoice.customer.display_name:''),
						'shipping_address':(rsp.invoice.shipping_address!=null?rsp.invoice.shipping_address:''),
						'shipment_number':(snum!=null?snum:'1'),
						'weight':'',
						'weight_units':'10',
						'shipping_company':'',
						'tracking_number':'',
						'boxes':'',
						'pack_date':M.dateFormat(new Date()),
						'ship_date':'',
						'invoice_items':[],
						'freight_amount':'',
						'items':[],
						'customer_notes':(rsp.invoice.customer_notes!=null?rsp.invoice.customer_notes:''),
						};
					if( rsp.invoice.po_number != null && rsp.invoice.po_number != '' ) {
						p.sections.invoice.list.invoice_po_number.visible = 'yes';
					}
					if( rsp.invoice.shipping_address != null && rsp.invoice.shipping_address != '' ) {
						p.sections.invoice.list.shipping_address.visible = 'yes';
					}
					if( M.curBusiness.sapos.settings['shipments-default-shipper'] != null ) {
						p.data['shipping_company'] = M.curBusiness.sapos.settings['shipments-default-shipper'];
					}
					if( M.curBusiness.sapos.settings['shipments-default-weight-units'] != null ) {
						p.data['weight_units'] = M.curBusiness.sapos.settings['shipments-default-weight-units'];
					}
					if( rsp.invoice.items != null ) {
						for(i in rsp.invoice.items) {
							if( rsp.invoice.items[i].item.required_quantity > 0 ) {
//							if( rsp.invoice.items[i].item.shipped_quantity < rsp.invoice.items[i].item.quantity ) {
								p.data.invoice_items.push(rsp.invoice.items[i]);
							}
						}
					}
					p.refresh();
					p.show(cb);
				});
		}
	};

	this.updateEditItems = function() {
		M.api.getJSONCb('ciniki.sapos.invoiceGet', {'business_id':M.curBusinessID,
			'invoice_id':this.edit.invoice_id, 'inventory':'yes'}, function(rsp) {
				if( rsp.stat != 'ok' ) {
					M.api.err(rsp);
					return false;
				}
				var p = M.ciniki_sapos_shipment.edit;
				p.data.invoice_items = [];
				if( rsp.invoice.items != null ) {
					for(i in rsp.invoice.items) {
						if( rsp.invoice.items[i].item.required_quantity > 0 ) {
//						if( rsp.invoice.items[i].item.shipped_quantity < rsp.invoice.items[i].item.quantity ) {
							p.data.invoice_items.push(rsp.invoice.items[i]);
						}
					}
				}
				p.refreshSection('invoice_items');
				p.show();
			});
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
//					M.ciniki_sapos_shipment.showEdit();
					var p = M.ciniki_sapos_shipment.edit;
					p.setFieldValue('status', rsp.shipment.status);
					p.data.status = rsp.shipment.status;
					p.data.invoice_items = [];
					var invoice_items = rsp.shipment.invoice_items;
					if( invoice_items != null ) {
						for(i in invoice_items) {
							if( invoice_items[i].item.required_quantity > 0 ) {
//							if( invoice_items[i].item.shipped_quantity < invoice_items[i].item.quantity ) {
								p.data.invoice_items.push(invoice_items[i]);
							}
						}
					}
					p.data.items = rsp.shipment.items;
					p.refreshSection('invoice_items');
					p.refreshSection('items');
				});
		} else {
			var c = this.edit.serializeForm('yes');
			var old_status = this.edit.formFieldValue(this.edit.sections.details.fields.status, 'status');
			c += '&status=10';	// Force status
//			M.api.postJSONCb('ciniki.sapos.shipmentAdd', {'business_id':M.curBusinessID,
//				'invoice_id':this.edit.invoice_id}, c, function(rsp) {
//					if( rsp.stat != 'ok' ) {
//						M.api.err(rsp);
//						return false;
//					}
//					M.ciniki_sapos_shipment.edit.shipment_id = rsp.shipment.id;
			var c = '&item_id=' + item_id + '&quantity=' + quantity;
			M.api.postJSONCb('ciniki.sapos.shipmentItemAdd', {'business_id':M.curBusinessID,
				'invoice_id':this.edit.invoice_id}, c, function(rsp) {
					if( rsp.stat != 'ok' ) {
						M.api.err(rsp);
						return false;
					}
					var p = M.ciniki_sapos_shipment.edit;
					p.sections._buttons.buttons.print.visible = 'yes';
					p.shipment_id = rsp.shipment.id;
					p.data = rsp.shipment;
					var invoice_items = rsp.shipment.invoice_items;
					p.data.invoice_items = [];
					if( invoice_items != null ) {
						for(i in invoice_items) {
							if( invoice_items[i].item.required_quantity > 0 ) {
//							if( invoice_items[i].item.shipped_quantity < invoice_items[i].item.quantity ) {
								p.data.invoice_items.push(invoice_items[i]);
							}
						}
					}
					p.refreshSection('invoice_items');
					p.refreshSection('items');
					p.refreshSection('_buttons');
				});
//				});
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
						M.ciniki_sapos_shipment.showEdit();
//						window.open(M.api.getUploadURL('ciniki.sapos.packingSlipPDF',
//							{'business_id':M.curBusinessID, 'shipment_id':sid}));
						M.api.openPDF('ciniki.sapos.packingSlipPDF',
							{'business_id':M.curBusinessID, 'shipment_id':sid});
					});	
			} else {
//				window.open(M.api.getUploadURL('ciniki.sapos.packingSlipPDF',
//					{'business_id':M.curBusinessID, 'shipment_id':sid}));
				M.api.openPDF('ciniki.sapos.packingSlipPDF',
					{'business_id':M.curBusinessID, 'shipment_id':sid});
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
