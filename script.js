
var xcomSites;
var xcomHeaders;
var xcom_remote_url;  
var xcom_srch_str;
function xcom_localSave(a_id) {

   var fn_sel = document.getElementById('xcom_sel');
   if(fn_sel.selectedIndex > 0) {
       xcom_setValue('xcom_pageid',a_id);
        xmlrpc();
        return;
   }
   
   if(a_id) {
      xcom_setValue('xcom_pageid',a_id);
      if(!window.confirm(JSINFO['savelocalfile']  + ' ' + a_id)) return;
   }  

  var params = "";
  
  var id =a_id ? a_id : xcom_getInputValue('xcom_pageid');
  var params = 'id='+id;

   var jobj = xcom_json_ini('xcom_localpwd','xcom_locuser');
   jobj['url'] = JSINFO['url'];
   var str =JSON.stringify(jobj); 
   params +='&local=' + str;

    var jobj = xcom_json_ini('xcom_pwd','xcom_url','xcom_user');
    str =JSON.stringify(jobj); 
    params +='&remote=' + str;
   //if(!confirm("params: " + params)) return;
   
   var status =new Array("Save remote: ("+ id +  ") to Local wiki");
   xcom_query_status(status);
  
         jQuery.ajax({
            url: DOKU_BASE + 'lib/plugins/xcom/scripts/xcom_save.php',
            data: params,         
            type: 'POST',
            dataType: 'html',         
            success: function(data)
            {  
               data = decodeURIComponent(data);                      
               xcom_show('xcom_results');
               xcom_print_data('dokuwiki.copy', data, false); 
            }
        });
}

function xmlrpc() {         
       xcom_hide_all_views();
       xcom_hide('xcom_view');
       xcom_hide('xcom_pre_title');
       xcom_hide('xcom_htm_title');
       xcom_hide('xcom_editable_title');
       xcom_remote_url = xcom_getInputValue('xcom_url'); 	   
       xcom_remote_url = xcom_remote_url.replace(/[\/\\]$/,"");
	   xcom_remote_url += '/doku.php?';
       	   
       xcom_clear('xcom_qstatus',false); 
       var options =  xcom_params(); 
       if(!options) return;
       xcom_query_status(options);
       var func = options[0];
       if(!func) {
          alert('No function selected');
          return false; 
        }  
           
       var other=false;
       var params =  'params=' + JSON.stringify(options);
       params = params.replace(/\s*__comma__\s*/g,',');
       if(typeof options[2] == 'object' && options[2] !== null ) {
          try {
              if(options[2].hasOwnProperty('hash')) {
                 other = 'hash';
              }
          } catch(e) {
          }
       }
       var array_types = {'dokuwiki.getPagelist':1,'plugin.xcom.pageVersions':1,'plugin.xcom.getPageInfo':1,'wiki.getAllPages':1, 'wiki.getAttachmentInfo':1,'wiki.getAttachments':1, 'wiki.getBackLinks':1,
       'wiki.getRecentChanges':1,'wiki.listLinks':1,'dokuwiki.search':1,'plugin.xcom.getMedia':1, 'plugin.xcom.listNamespaces':1};       
       var jobj = xcom_json_ini('xcom_pwd','xcom_url','xcom_user');
       str =JSON.stringify(jobj); 
       params += '&credentials=' + str;      
       params += '&debug=' + document.getElementById('xcom_debug').checked;

       //  if(!confirm(params)) return;
         jQuery.ajax({
            url: DOKU_BASE + 'lib/plugins/xcom/scripts/xml.php',
            data: params,         
            type: 'POST',
            dataType: 'html',         
            success: function(data)
            {  
            if(!array_types.hasOwnProperty(func)) {
                try {
               data = decodeURIComponent(data);                                              
                }   
                catch(err){
                    console.log("By-passed decoding string returned by " + func + ': ' + err.message );
                }                
            }
               xcom_show('xcom_results');
               xcom_print_data(func, data,other); 
            }
        });
      
        var fn_sel = document.getElementById('xcom_sel');
        fn_sel.selectedIndex = 0;
         return false;
}

function xcom_print_data(fn, data,other) {
   var id = 'xcom_pre';

   var table_calls = {     
     'dokuwiki_getPagelist': (other=='hash') ? ['id','rev', 'mtime' ,'size','hash'] : ['id','rev', 'mtime' ,'size'] ,      
     'plugin_xcom_pageVersions': ['user','ip','type','sum','modified','version' ],
     'plugin_xcom_getPageInfo': ['name','lastModified','author','version'],
     'wiki_getAllPages': ['id', 'perms', 'size', 'lastModified'],
     'dokuwiki_search': ['id', 'score', 'rev', 'mtime','size','snippet'],
     'plugin_xcom_getMedia': ['Media files'],
     'wiki_getAttachments': ['id','size','lastModified'],
     'wiki_listLinks': ['type', 'page','href'], 
     'wiki_getAttachmentInfo': ['id','lastModified','size'],
     'plugin_xcom_listNamespaces': ['Namespace Directories'],
     'wiki_getRecentChanges': ['name', 'lastModified', 'author','version','size'],
     'wiki_getBackLinks' : ['Backlinks'], 
   };
   xcomHeaders = table_calls;
   
	        switch(fn) 
         {
            case 'wiki.getPage':                 // (string) raw Wiki text                 
            case 'wiki.getPageVersion':      // (string) raw Wiki text 
                  id = 'xcom_editable' ;
                  break;   
            case 'wiki.getPageHTML':      // (string) rendered HTML 
                 id = 'xcom_htm';
                 break;
            case 'dokuwiki.getPagelist':
            case 'plugin.xcom.pageVersions':
            case 'plugin.xcom.getPageInfo':
            case 'wiki.getAllPages':
            case 'dokuwiki.search':
            case 'plugin.xcom.getMedia':
            case 'wiki.getAttachments':
            case  'wiki.listLinks':
            case  'wiki.getAttachmentInfo':    
            case  'plugin.xcom.listNamespaces':  
            case  'wiki.getRecentChanges':
            case 'wiki.getBackLinks':
                 id = 'xcom_htm';
                 try {
                     var obj = jQuery.parseJSON(data);                                           
                 } catch(e) {
                    id = 'xcom_pre';   // not a table, use code view, probably error msg                                 
                    data = decodeURIComponent(data);                    
                     break;
                 }
                    /** 
                       handle tables
                    */
                     if(obj) 
                    {   
                        var fncall = fn.replace(/\./g,'_');                      
                        data = xcom_thead(table_calls[fncall]); 
                         if(fn == 'plugin.xcom.getPageInfo' || fn ==  'wiki.getAttachmentInfo') {
                                data +=  xcom_hash(obj);  //straight single hash
                           }     
                          else if (fn == 'plugin.xcom.getMedia' ||
                                   fn =='plugin.xcom.listNamespaces' ||
                                   fn == 'wiki.getBackLinks'                     
                                 ) {
                               data +=  xcom_onedim(obj);
                           }                          
                          else {
                               data+=xcom_multidim(obj,fn);  // array of arrays
                          }
                    }                   
                    data += xcom_tclose();  //end tables
                break;   
             case 'wiki.putPage':  
             case 'dokuwiki.appendPage':  
                  id = 'xcom_editable';
                  break;
               default:     
                   break;                  
        }   // end switch
    
    var d = document.getElementById(id);  
    if(id == 'xcom_editable') {
        xcom_setValue(id,data);
    }    
    else {
        d.innerHTML=  data;
        }
    xcom_show(id);
    var title = id + '_title';
    xcom_show(title);
}

function xcom_multidim(obj,func) {
        var data = "";
        
        for(var i in obj) {      
         data +="\n<tr>";                                                        
         for(var j in obj[i]) {                        
             var r = obj[i][j];
              if(j == 'lastModified' && func == 'wiki.getRecentChanges') {
                 r = obj[i]['version'];        
                var date_time = new Date(r * 1000);
                var month = (date_time.getMonth() + 1) > 9 ? (date_time.getMonth() + 1) : '0' + (date_time.getMonth() + 1);
                var day =  date_time.getDate() > 9  ? date_time.getDate() : '0'+ date_time.getDate();
                r = date_time.getFullYear() + "-" + month + "-" + day + " " + date_time.getHours() + ":" + date_time.getMinutes() + ":" + date_time.getSeconds() 
                 
              }
             row = xcom_td(j,r,func);            //type, value, function
             if(row) data += row;
         } 
       }
       
       return data;
}

function xcom_hash(obj) {
         var data ="\n<tr>";   
         for(var i in obj) { 
           data += xcom_td(i,obj[i]);            
         }
         return data;
}

function xcom_onedim(obj) {
        var data ="\n<tr>";   
         for(var i in obj) { 
           data += xcom_td(i,obj[i]) + "\n<tr>";   
         }
         return data;        
}

function xcom_thead(args) {
  var row = "<table class ='xcom_center'>\n<tr>";
  for (i=0; i<args.length; i++) {
     row += '<th>' + args[i] + '</th>';
  }
   return row + "</tr>\n";
}

function xcom_td(type,val,fn) {
     
     if(fn) 
     {        
         var is_header = false;
         var fncall = fn.replace(/\./g,'_');                                
         var headers = xcomHeaders[fncall];
         for(var i = 0; i< headers.length; i++) {            
             if(type == headers[i]) {
                 is_header=true;
                 break;
             }
        }    
        if(!is_header) return;
    }

    //alert(type + '=' + val);
    if(type == 'modified' || type == 'lastModified' && typeof val == 'object') {    
      //  var min =val['minute'] ?  val['minute'] : val['minute'];
	    if (typeof val !== 'undefined' && val['year']) {
        var d = new Date( val['year'],val['month']-1,val['day'],val['hour'],val['minute'], val['second']);
        val = d.toUTCString();
	      }	

    }
    else if(type == 'rev' || type == 'mtime') {
       var d = new Date(val*1000);
       val = d.toUTCString();
    }
    else if(type == 'size') {
        val += ' bytes';
    }
     else if(type == 'id'  && fn=='dokuwiki.search') {
          return '<td class ="xcom_id">'+ xcom_search_url(val) +'</td>';
    }
    else if(type == 'id' || type == 'href' || (type =='page' && fn == 'wiki.listLinks')) {                   
       var display = val;
       if(val.length > 40) {
          var a = val.substring(0,40) + ". . . .<br />";  
          var b = val.substring(40);
          if(b.length > 7) { 
             display = a + '&nbsp;&nbsp;&nbsp;&nbsp;' + b;
          }   
        } 
         if(type == 'id') {        
            return '<td><a href="javascript:xcom_localSave(\'' + val + '\');void 0;">' +display +'</a></td>';         
         }
         val =display;
    }
    else if(type == 'snippet') {
        return '<tr><td class="xcom_none">&nbsp;</td><td colspan = "4" class="xcom_snippet">' + val + '</td>';
    }
    else if(type == 'title' && fn=='dokuwiki.search') return "";  //skip title, screws up the table design
    if(typeof val == 'object') val = "none";
     return '<td>' + val + '</td>'      
}

function xcom_tclose() {
  return "</table>\n";
}

function xcom_search_url(pageid) {
	 if ( typeof xcom_srch_str == 'undefined' ) {        
        xcom_srch_str =xcom_getInputValue('xcom_opts');
      }	
	 var qs = '&'+ xcom_srch_opts(); 	 
	 return '<a href = "' + xcom_remote_url + 'id=' +pageid + qs +'" target = \"_blank\">' + pageid + '</a>';
}	

function xcom_srch_opts() {  // for search function
	var srch_str =xcom_getInputValue('xcom_opts');
    srch_str = srch_str.replace(/^\s+/,"");
    srch_str = srch_str.replace(/\s+$/,"");
    srch_str = srch_str.replace(/\s+\"/,'\"');
    srch_str = srch_str.replace(/\"\s+/,'\"'); 
  
    var tmp = srch_str.split('\"');
    if(tmp.length == 1) {
      tmp = srch_str.split(/\s+/g);
    }	
    var result = "";
    for(i=0; i<tmp.length; i++) {
      if(tmp[i]) {
       result += "s[]=" + tmp[i]; 
       if(tmp[i+1]) {
         result += '&';
       }
      }
    }
  result = result.replace(/=\s+/g, '='); 
  result = result.replace(/\s*&\s*/g, '&'); 
  return result;
}
function xcom_check_opts(fn,page,opts) {
      page = page.trim();
    var regex;
    var skip_opts_cnt = false;
    switch(fn) {
         case 'wiki.getAllPages':
         case 'dokuwiki.getTitle':
            if((!page || page.trim().length === 0) && !opts) {              
                return true;
            }          
           xcom_err_msg('wrong_count',fn,'no_opts');
             return false;           
        case 'wiki.aclCheck': 
            skip_opts_cnt = true;
        case 'wiki.getPage':  
        case 'plugin.xcom.getMedia': 
        case 'wiki.getAttachmentInfo':
        case 'wiki.deleteAttachment': 
        case 'wiki.listLinks':
        case 'wiki.getBackLinks':
        case 'plugin.xcom.getPageInfo':
        case 'wiki.getPageHTML': 
            if(opts && !skip_opts_cnt) {
                xcom_err_msg('wrong_count',fn,'no_opts');
                return false;
            }            

             if(page.match(/[^0-9a-z_\:\.\-]+/g)) { 
                xcom_err_msg('bad_id');
                return false;
            }
            return true;
           
        case 'wiki.getRecentChanges': 
        case 'wiki.getRecentMediaChanges':
             if(page || page.length) {              
                xcom_err_msg('wrong_count',fn, 'date_only');
                return false;
            }     
		     regex = RegExp('^\s*\\d\\d\\d\\d-\\d\\d-\\d\\d\s*$');
			opt = opts.trim(); 
            if(!regex.test(opt)) {
                xcom_err_msg('date_err');               
                return false;
            }  
            break	
     
	    case 'dokuwiki.getPagelist': //(hash),(depth:n)	    
            if(!page  || !opts) {
                xcom_err_msg(fn,'param-err');
                return false;
            }
            break;
			
        case 'dokuwiki.search': //string query
		if(!opts) {
                xcom_err_msg('srch_string');
		        return false;
			}
               break;
        case 'dokuwiki.appendPage':         
        case 'wiki.putPage':  
            if(!page) {
                alert("Page id missing");
                return false;
            }
            var regex_m = RegExp('\(minor;\s*(1|true)\s*\)');
            var regex_s = RegExp('\\(sum;[\\w\\s\\d;\\.\\:\\[\\]\\{\\}]+\\)');              
            if(regex_m.test(opts) || regex_s.test(opts)) break;
            alert("needs sum or minor edit statement");
            return false;
                  
            break;                  
/*     

        case 'plugin.xcom.pageVersions': (string) [[doku>:pagename]] , (int) offset
            break;
        case 'wiki.getAttachments': (String) namespace, (array) options (#pattern#)
            break;
        case 'plugin.acl.addAcl': (String) scope, (String) user|@group 
            break;	
        case 'plugin.acl.delAcl':String) scope, (String) user|@group, (int) permission 
            break;	
        case 'plugin.xcom.listNamespaces':(String) namespace id, (Array) (id1;id2. . .)
            break;	*/
        default:
          		
    }
    return true;
}

function xcom_params() {
    var params = new Array(),i=0;
    var optstring =  xcom_getInputValue('xcom_opts');  //Params from User-created Query/Options box
   
     
    var opts = ""; 
    var matches;
    optstring = optstring.replace(/\s+$/,"");   
    optstring = optstring.replace(/^\s+/,"");   
    optstring = optstring.replace(/;\s+/,";");   
   
    optstring = optstring.replace(/\((.*?)\)/g,function(a) {       
        return a.replace(/,/g,' __comma__ ');
    });
  
    optstring = optstring.replace(/\#(.*?)\#/g,function(a) {       
        return a.replace(/,/g,'__comma__');
    });
    
    if(optstring) opts = optstring.split(/,/);          
   
     
    for(var p=0; p<opts.length; p++) {	
        if(!opts[p] || !opts[p].match(/^\s*\(/)) break;    
        var isarray = xcom_getArray(opts[p]);    
         if(isarray) {
             if(isarray[0] == 'hash')   {       
                opts[p] ={'hash':'1'};
                break;
             }    
             else if(isarray[0].match(/#/))   { 
                opts[p] ={'pattern' : isarray[0]};
                break;
             }    
             else if(isarray[0].match(/sum/))   {  
                opts[p] ={'sum' : isarray[1]};
                break;
             } 
             else if(matches = isarray[0].match(/minor/))   {                 
                opts[p] ={'minor': isarray[1]};
                break;
             }                              
             else {
                 opts[p] =isarray;
                 break;
             }
          }
    }
    
     var fn_sel = document.getElementById('xcom_sel');       
  
     for(var n=0; n<opts.length; n++) {  
        opts[n] = xcom_timeStamp(opts[n]);
    }
    
    if(fn_sel.selectedIndex > 0) {  
        params[i]  = fn_sel.options[fn_sel.selectedIndex].value;
        }
     else 
     {
        if(!opts) return false;       
       
       return params[i] = opts;
    }     
     
    var page = document.getElementById('xcom_pageid').value;
	
    var opstatus = xcom_check_opts(params[i],page,optstring);
	if(!opstatus) return false;
    if(page)  {       
       if(params[0] == 'dokuwiki.search') {  // add page to search query
             opts[0] =  opts[0] + " " + page;            
       }
       else params[++i] = page;
    }
     else {
         if(params[0]=='plugin.xcom.listNamespaces') {
            params[++i] = '0';
         }
    }     
    if(params[0]=='wiki.putPage' || params[0]=='dokuwiki.appendPage') {
            params[++i] = xcom_escape(xcom_getInputValue('xcom_editable'));             
     }   

     //assign options to parameter array
    if(opts.length) {
          for(j=0;j<opts.length;j++) {
            opts[j] = xcom_timeStamp(opts[j]);
            params[++i] = opts[j]; 
          }
    }
    
    return params; 
}

function xcom_getArray(opt) {

   if(!opt) return false;
   var matches;
   try{
    if(matches = opt.match(/\((.*?)\)/)) {          
         ar = matches[1].split(/;/);  
          return ar;
       }
    }
    catch(e) { }
    return false;
}

 /* returns formatted timestamp if date, otherwise returns the option */
function xcom_timeStamp(opt) {        
    try{
        if(opt.match(/\d\d\d\d-\d\d-\d\d/)) {
           var d = new Date(opt);
           var unixtime = parseInt(d.getTime() / 1000);
           if(unixtime) {
			   unixtime += 86400;	//needs added day for accurate time		  
               return unixtime;
           }
        }
    }catch(e) { 
    }
    return opt;
}

function xcom_escape(data) {
   data = data.replace(/&/g,"%26");
   return  data;
}
/**
  Format and output query on status line
*/
function xcom_query_status(options) {
  
   if(typeof options != 'object'  && !(options instanceof Array)) return;
      
   var q = options.join(',&nbsp;');
 
   if(q.length > 70) {
      q = q.substring(0,70) + '.  .  .';
   }
   document.getElementById('xcom_qstatus').innerHTML = q;
   
}

function xcom_select(sel) {
    var which =sel.options[sel.selectedIndex].value;
    if(!which) return;
    xcom_setValue('xcom_url',xcomSites[which]['url']);
    xcom_setValue('xcom_pwd',xcomSites[which]['pwd']);
    xcom_setValue('xcom_user',xcomSites[which]['user']);
}

function xcom_toggle(which) {
  jQuery(which).toggle();
  var state = jQuery(which).css('display');  
  if(state != 'none')  {
      xcom_show('xcom_results');
  }
  var title = which + '_title';
  jQuery(title).toggle();  
}

function xcom_show(which) {
   var d = document.getElementById(which);
   if(d) d.style.display = 'block'; 
}

function xcom_hide(which) {
  var d = document.getElementById(which);
  if(d) d.style.display = 'none'; 
}
function xcom_hide_all_views() {
    xcom_hide('xcom_editable');
    xcom_hide('xcom_pre');
    xcom_hide('xcom_htm');
    xcom_hide('xcom_editable_title');
    xcom_hide('xcom_pre_title');
    xcom_hide('xcom_htm_title');
    xcom_hide('xcom_results');
    
}

function xcom_clear(which) {
  if(which == 'xcom_editable') {
     xcom_setValue(which,"");     
     return;
  }
  document.getElementById(which).innerHTML= '';   
  if(arguments.length > 1) return;
  xcom_hide(which);
 } 
/**
      creates credentials array for Json encoding
*/
function xcom_json_ini() {
    jobj = {};
     for (i=0; i<arguments.length; i++) {
        var val = xcom_getInputValue(arguments[i]);
        var key = (arguments[i].split(/_/))[1];
        key = key.replace(/local/,"");
        key = key.replace(/loc/,"");
        jobj[key] = val;
     }
     return jobj;
}

function xcom_getInputValue(item) {  
    var d = document.getElementById(item);
    if(!d) return;
    return  d.value;
}


function xcom_setValue(item,val) {  
   var d = document.getElementById(item);
   if(!d) return;
   d.value = val; 
}

/* used for checking options */
function xcom_msg(msg) {
  id ='xcom_pre';
   xcom_show('xcom_results');   
  var d = document.getElementById(id);  
  d.innerHTML = msg;
  xcom_show('xcom_pre');
}

/**
   JSON.stringify combines elements from both of below:
      http://blogs.sitepointstatic.com/examples/tech/json-serialization/json-serialization.js
      https://gist.github.com/chicagoworks/754454
*/
var JSON = JSON || {};
// implement JSON.stringify serialization
JSON.stringify = JSON.stringify || function (obj) {
	var t = typeof (obj);
	if (t != "object" || obj === null) {
		// simple data type
		if (t == "string") obj = '"'+obj+'"';
		return String(obj);
	} else {
    
		// recurse array or object
		var n, v, json = [], arr = (obj && obj.constructor == Array);
		for (n in obj) {
                v = obj[n];
                t = typeof(v);
                if (obj.hasOwnProperty(n)) {
                    if (t == "string") {
                        v = '"' + v + '"';
                    } else if (t == "object" && v !== null){
                        v = jQuery.stringify(v);
                    }

			json.push((arr ? "" : '"' + n + '":') + String(v));
		}
            }

		return (arr ? "[" : "{") + String(json) + (arr ? "]" : "}");
	}
};


jQuery( document ).ready(function() {     
       /* drop-down function menu with tool tips */
	   /* xcom_query_types is array of xmlrpc functions below */ 
       var sel = document.getElementById('xcom_sel');   
       if(sel) {
       var titles = JSINFO['xcom_qtitles'];
       for(i=0; i<xcom_query_types.length; i++) {
           var text = xcom_query_types[i].match(/^plugin\./) ? xcom_query_types[i].replace(/^plugin\./,"") : (xcom_query_types[i].split('.'))[1]; 
            var newopt = new Option(text,xcom_query_types[i]);
            if(titles[xcom_query_types[i]]) newopt.title = titles[xcom_query_types[i]];
            else newopt.title = xcom_query_types[i];
            sel.add(newopt);
       }

         var selsites = document.getElementById('xcom_selsites');   
         if(selsites) {
             xcomSites = JSINFO['xcom_sites'];
             for (var i in xcomSites) {
                var newopt = new Option(i,i);
                selsites.add(newopt);
             }
           }
       }  // drop-down menu end
	   
   var img_path = DOKU_BASE + 'lib/plugins/xcom/images/';
   var eyes  = {
        'black':  img_path + 'eye_blk.png',
        'blue':  img_path + 'eye_blue.png',
   };
       
   jQuery("img").click(function () {   
      if(jQuery(this).attr("src").match(/eye_blue/)) {
           var which = jQuery(this).attr("id").match(/loc/) ?  "#xcom_localpwd" : "#xcom_pwd";
           jQuery(this).attr("src",eyes.black);
           jQuery(which).attr("type","password");
           jQuery(this).attr("title",JSINFO['pwdview']);           
       }
       else if(jQuery(this).attr("src").match(/eye_blk/)) {      
          var which = jQuery(this).attr("id").match(/loc/) ?  "#xcom_localpwd" : "#xcom_pwd";
          jQuery(this).attr("src",eyes.blue);
          jQuery(which).attr("type","text");
           jQuery(this).attr("title",JSINFO['pwdhide']);                     
       }
});

jQuery( "#xcom_eye" ).on( "mouseover", function() {
jQuery( this ).css( "cursor", "pointer" );
});

jQuery( "#xcom_eye" ).on( "mouseout", function() {
jQuery( this ).css( "cursor", "default" );
});
       
jQuery( "#xcom_loceye").on( "mouseover", function() {
jQuery( this ).css( "cursor", "pointer" );
});

jQuery( "#xcom_loceye" ).on( "mouseout", function() {
jQuery( this ).css( "cursor", "default" );
});
       
});

function xcom_rollover(el,underline) {
if(underline) 
  el.style.textDecoration ='underline';
else el.style.textDecoration = 'none';

}
var xcom_query_types=new Array(
'dokuwiki.getPagelist',
'dokuwiki.search',
'dokuwiki.getTitle',
'dokuwiki.appendPage',
'wiki.aclCheck',
'wiki.getPage',
'wiki.getPageVersion',
'plugin.xcom.pageVersions',
'plugin.xcom.getPageInfo',
'wiki.getPageHTML',
'wiki.putPage',
'wiki.listLinks',
'wiki.getAllPages',
'wiki.getBackLinks',
'wiki.getRecentChanges',
'wiki.getAttachments',
'wiki.getAttachmentInfo',
'wiki.getRecentMediaChanges',
'plugin.acl.addAcl',
'plugin.acl.delAcl',
'plugin.xcom.getMedia',
'plugin.xcom.listNamespaces'
);

function xcom_err_msg() {
    var i,msg="";
    if(arguments.length == 1) {
        xcom_msg(LANG.plugins.xcom[arguments[0]]);
        return;
    } 
    
    for(i=0; i<arguments.length;i++) {  
       if(typeof LANG.plugins.xcom[arguments[i]] == 'undefined') {
           msg += '<b>'+ arguments[i] + "</b> ";
       }
       else msg+=LANG.plugins.xcom[arguments[i]] + " ";       
    }
    xcom_msg(msg);
}