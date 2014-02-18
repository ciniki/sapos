//
// This panel will create the form for processing credit card transactions
//
function ciniki_sapos_terminal() {
	this.transactionSources = {
		'10':'Paypal',
		'20':'Visa',
		'30':'Mastercard',
		'90':'Interac',
		'100':'Cash',
		'105':'Check',
		'110':'Email Transfer',
		'120':'Other',
		};
	this.paypalCCTypes = {
		'visa':'Visa',
		'mastercard':'Mastercard',
		'discover':'Discover',
		'amex':'Amex',
		};
	this.paypalMonths = {
		'1':'1',
		'2':'2',
		'3':'3',
		'4':'4',
		'5':'5',
		'6':'6',
		'7':'7',
		'8':'8',
		'9':'9',
		'10':'10',
		'11':'11',
		'12':'12',
		};
	this.paypalYears = {
		'2014':'14',
		'2015':'15',
		'2016':'16',
		'2017':'17',
		'2018':'18',
		'2019':'19',
		'2020':'20',
		'2021':'21',
		'2022':'22',
		'2023':'23',
		'2024':'24',
		'2025':'25',
	};
	this.paypalCurrencies = {
		'USD':'USD',
		'CAD':'CAD',
	};
	this.paypalCountryCodes = {
		'':'',
		'CA':'Canada',
		'US':'United States',
		};
	this.paypalStateCodes = {
		'':'',
		'AB':'Alberta',
		'BC':'British Columbia',
		'MB':'Manitoba',
		'NB':'New Brunswick',
		'NL':'Newfoundland and Labrador',
		'NT':'Northwest Territories',
		'NS':'Nova Scotia',
		'NU':'Nunavut',
		'ON':'Ontario',
		'PE':'Prince Edward Island',
		'QC':'Quebec',
		'SK':'Saskatchewan',
		'YT':'Yukon',
		'AL':'Alabama',
		'AK':'Alaska',
		'AS':'American Samoa',
		'AZ':'Arizona',
		'AR':'Arkansas',
		'CA':'California',
		'CO':'Colorado',
		'CT':'Connecticut',
		'DE':'Delaware',
		'DC':'District of Columbia',
		'FM':'Federated States of Micronesia',
		'FL':'Florida',
		'GA':'Georgia',
		'GU':'Guam',
		'HI':'Hawaii',
		'ID':'Idaho',
		'IL':'Illinois',
		'IN':'Indiana',
		'IA':'Iowa',
		'KS':'Kansas',
		'KY':'Kentucky',
		'LA':'Louisiana',
		'ME':'Maine',
		'MH':'Marshall Islands',
		'MD':'Maryland',
		'MA':'Massachusetts',
		'MI':'Michigan',
		'MN':'Minnesota',
		'MS':'Mississippi',
		'MO':'Missouri',
		'MT':'Montana',
		'NE':'Nebraska',
		'NV':'Nevada',
		'NH':'New Hampshire',
		'NJ':'New Jersey',
		'NM':'New Mexico',
		'NY':'New York',
		'NC':'North Carolina',
		'ND':'North Dakota',
		'MP':'Northern Mariana Islands',
		'OH':'Ohio',
		'OK':'Oklahoma',
		'OR':'Oregon',
		'PW':'Palau',
		'PA':'Pennsylvania',
		'PR':'Puerto Rico',
		'RI':'Rhode Island',
		'SC':'South Carolina',
		'SD':'South Dakota',
		'TN':'Tennessee',
		'TX':'Texas',
		'UT':'Utah',
		'VT':'Vermont',
		'VI':'Virgin Islands',
		'VA':'Virginia',
		'WA':'Washington',
		'WV':'West Virginia',
		'WI':'Wisconsin',
		'WY':'Wyoming',
		'AA':'Armed Forces Americas',
		'AE':'Armed Forces',
		'AP':'Armed Forces Pacific',
		};
	this.init = function() {
		this.paypal = new M.panel('Paypal Terminal',
			'ciniki_sapos_terminal', 'paypal',
			'mc', 'medium', 'sectioned', 'ciniki.sapos.terminal.paypal');
		this.paypal.data = {};
		this.paypal.invoice_id = 0;
		this.paypal.sections = {
			'creditcard':{'label':'Credit Card', 'fields':{
				'type':{'label':'Type', 'type':'toggle', 'toggles':this.paypalCCTypes},
				'number':{'label':'Number', 'type':'text'},
				'expire_month':{'label':'Expiry Month', 'type':'toggle', 'toggles':this.paypalMonths},
				'expire_year':{'label':'Expiry Year', 'type':'toggle', 'toggles':this.paypalYears},
				'cvv2':{'label':'Security Code', 'type':'text', 'size':'small'},
				'total':{'label':'Amount', 'type':'text', 'size':'small'},
				'currency':{'label':'Currency', 'type':'toggle', 'toggles':this.paypalCurrencies},
				'system':{'label':'System', 'type':'toggle', 'default':'live', 'toggles':{'live':'Live', 'test':'Test'}},
				}},
			'payer':{'label':'Payer', 'fields':{
				'first_name':{'label':'First Name', 'type':'text'},
				'last_name':{'label':'Last Name', 'type':'text'},
				'line1':{'label':'Address', 'type':'text'},
				'line2':{'label':'', 'type':'text'},
				'city':{'label':'City', 'type':'text', 'size':'medium'},
				'state':{'label':'Province/State', 'type':'select', 'options':this.paypalStateCodes},
				'postal_code':{'label':'Postal/Zip', 'type':'text', 'size':'small'},
				'country_code':{'label':'Country', 'type':'select', 'options':this.paypalCountryCodes},
				'phone':{'label':'Phone', 'type':'text', 'size':'medium'},
				}},
			'_buttons':{'label':'', 'buttons':{
				'save':{'label':'Process Payment', 'fn':'M.ciniki_sapos_terminal.processPaypal();'},
				}},
		};
		this.paypal.fieldValue = function(s, i, d) {
			if( this.data[i] != null ) { return this.data[i]; }
			if( d.default != null ) { return d.default; }
			return '';
		};
		this.paypal.addClose('Cancel');
	}

	this.start = function(cb, aP, aG) {
		args = {};
		if( aG != null ) { args = eval(aG); }
		var aC = M.createContainer(aP, 'ciniki_sapos_terminal', 'yes');
		if( aC == null ) {
			alert('App Error');
			return false;
		}

		this.showPaypalTerminal(cb, args.detailsFn());
	};

	this.showPaypalTerminal = function(cb, details) {
		this.paypal.reset();
		this.paypal.data = details;	
		this.paypal.invoice_id=(details.invoice_id!=null)?details.invoice_id:0;
		this.paypal.data.postal_code = details.postal;
		if( details.province != null ) {
			if( this.paypalStateCodes[details.province.toUpperCase()] != null ) {
				this.paypal.data.state = details.province.toUpperCase();
			}
			else {
				for(i in this.paypalStateCodes) {
					if( this.paypalStateCodes[i] == details.province ) {
						this.paypal.data.state = i;
						break;
					}
				}
			}
		}
		if( details.country != null ) {
			for(i in this.paypalCountryCodes) {
				if( this.paypalCountryCodes[i].toUpperCase() == details.country.toUpperCase() ) {
					this.paypal.data.country_code = i;
				}
			}
		}
		this.paypal.refresh();
		this.paypal.show(cb);
	};

	this.processPaypal = function() {
		var c = this.paypal.serializeForm('yes');
		M.api.postJSONCb('ciniki.sapos.paypalProcess', {'business_id':M.curBusinessID,
			'invoice_id':this.paypal.invoice_id}, c, function(rsp) {
				if( rsp.stat != 'ok' ) {
					M.api.err(rsp);
					return false;
				}
				M.ciniki_sapos_terminal.paypal.close();
			});
	};
}
