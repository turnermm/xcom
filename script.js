
var xcomSites;
var xcomHeaders;
function xcom_localSave(a_id) {
  var fn_sel = document.getElementById('xcom_sel');       
   if(fn_sel.selectedIndex > 0 && a_id) {
      xcom_setValue('xcom_pageid',a_id);
      xmlrpc();
      return;
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
   
   var status =new Array("Save remote: ("+ id +  ") to Local wiki");
   xcom_query_status(status);
  
         jQuery.ajax({
            url: DOKU_BASE + 'lib/plugins/xcom/scripts/xcom_save.php',
            async: false,
            data: params,         
            type: 'POST',
            dataType: 'html',         
            success: function(data)
            {  
               data = decodeURIComponent(data);                      
               xcom_show('xcom_results');
               xcom_print_data('dokuwiki.copy', data); 
            }
        });
}

function xmlrpc() {         
       xcom_hide_all_views();
       xcom_hide('xcom_view');
       xcom_hide('xcom_pre_title');
       xcom_hide('xcom_htm_title');
       xcom_hide('xcom_editable_title');
       

       xcom_clear('xcom_qstatus',false); 
       var options =  xcom_params();       
       if(!options) {
          alert('No function selected');
          return false; 
        }  
       xcom_query_status(options);
       var func = options[0];
       var params =  'params=' + JSON.stringify(options);
       var jobj = xcom_json_ini('xcom_pwd','xcom_url','xcom_user');
       str =JSON.stringify(jobj); 
       params += '&credentials=' + str;      
  
 //alert(params + "\n" + func);
  //return; 
         jQuery.ajax({
            url: DOKU_BASE + 'lib/plugins/xcom/scripts/xml.php',
            async: false,
            data: params,         
            type: 'POST',
            dataType: 'html',         
            success: function(data)
            {  
               data = decodeURIComponent(data);                                              
               xcom_show('xcom_results');
               xcom_print_data(func, data); 
            }
        });
         return false;
}

function xcom_print_data(fn, data) {
   var id = 'xcom_pre';

   var table_calls = {
     'dokuwiki_getPagelist':  ['id','rev', 'mtime' ,'size'],
     'wiki_getPageVersions': ['user','ip','type','sum','modified','version' ],
     'wiki_getPageInfo': ['name','lastModified','author','version'],
     'wiki_getAllPages': ['id', 'perms', 'size', 'lastModified'],
     'dokuwiki_search': ['id', 'score', 'rev', 'mtime','size'],
     'plugin_xcom_getMedia': ['Media files'],
     'wiki_getAttachments': ['id','size','lastModified'],
     'wiki_listLinks': ['type', 'page','href'], 
	 'wiki_getAttachmentInfo': ['id','lastModified','size'],
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
            case 'wiki.getPageVersions':
            case 'wiki.getPageInfo':
            case 'wiki.getAllPages':
            case 'dokuwiki.search':
            case 'plugin.xcom.getMedia':
            case 'wiki.getAttachments':
            case  'wiki.listLinks':
			case  'wiki.getAttachmentInfo':    
                 id = 'xcom_htm';
                 try {
                     var obj = jQuery.parseJSON(data);                                           
                 } catch(e) {
                    id = 'xcom_pre';   // not a table, use code view, probably error msg                                 
                     break;
                 }
                    /** 
                       handle tables
                    */
                     if(obj) 
                    {   
                        var fncall = fn.replace(/\./g,'_');                      
                        data = xcom_thead(table_calls[fncall]); 
                         if(fn == 'wiki.getPageInfo' || fn ==  'wiki.getAttachmentInfo') {
                                data +=  xcom_hash(obj);  //straight single hash
                           }     
                          else if (fn == 'plugin.xcom.getMedia') {
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
                  id == 'xcom_editable';
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
             row = xcom_td(j,r,func);            
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
    
    if(type == 'modified' || type == 'lastModified' && typeof val == 'object') {    
        var min =val['minute'] ?  val['minute'] : val['minut'];
        var d = new Date( val['year'],val['month']-1,val['day'],val['hour'],val['minute'], val['second']);
        val = d.toUTCString();
    }
    else if(type == 'rev' || type == 'mtime') {
       var d = new Date(val*1000);
       val = d.toUTCString();
    }
    else if(type == 'size') {
        val += ' bytes';
    }
     else if(type == 'id'  && fn=='dokuwiki.search') {
          return '<td class ="xcom_id">'+val +'</td>';
    }
    else if(type == 'id' || type == 'href' || (type =='page' && fn == 'wiki.listLinks')) {                   
       var display = val;
       if(val.length > 40) {
          var a = val.substring(0,40) + ". . . .<br />";  
          var b = val.substring(40)
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

function xcom_params() {
    var params = new Array(),i=0;
    var opts =  xcom_getInputValue('xcom_opts');  //Params from User-created Query/Options box
    opts = opts.replace(/^\s+/,"");
    opts = opts.replace(/\s+$/,"");
    if(opts) opts = opts.split(/,/);

    var fn_sel = document.getElementById('xcom_sel');       
    if(fn_sel.selectedIndex > 0) {
        params[i]  = fn_sel.options[fn_sel.selectedIndex].value;
     }
     else {
       if(!opts) return false;      
       for(n=0; n<opts.length; n++) {
         opts[n] = xcom_timeStamp(opts[n]);
       }
       return params[i] = opts;
     }     
  
    var page = document.getElementById('xcom_pageid').value;
  
    if(page)  {       
       if(params[0] == 'dokuwiki.search') {  // add page to search query
             opts[0] =  opts[0] + " " + page;            
       }
       else params[++i] = page;
    }   
    if(params[0]=='wiki.putPage' || params[0]=='dokuwiki.appendPage') {
            params[++i] = xcom_escape(xcom_getInputValue('xcom_editable'));             
            params[++i] = {'sum':"", 'minor':""};
     }     
    
    if(opts.length) {
          for(j=0;j<opts.length;j++) {
            opts[j] = xcom_timeStamp(opts[j]);
            params[++i] = opts[j]; 
          }
    }
    fn_sel.selectedIndex = 0;
    return params; 
}

function xcom_timeStamp(opt) {        
    try{
        if(opt.match(/\d\d\d\d-\d\d-\d\d/)) {
           var d = new Date(opt);
           var unixtime = parseInt(d.getTime() / 1000);
           if(unixtime) {
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
       var sel = document.getElementById('xcom_sel');   
       if(sel) {
       var titles = JSINFO['xcom_qtitles'];
       for(i=0; i<xcom_opts.length; i++) {
           var text = xcom_opts[i].match(/^plugin\./) ? xcom_opts[i].replace(/^plugin\./,"") : (xcom_opts[i].split('.'))[1]; 
            var newopt = new Option(text,xcom_opts[i]);
            if(titles[xcom_opts[i]]) newopt.title = titles[xcom_opts[i]];
            else newopt.title = xcom_opts[i];
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
       }
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
var xcom_opts=new Array(
'dokuwiki.getPagelist',
'dokuwiki.search',
'dokuwiki.getTitle',
'dokuwiki.appendPage',
'wiki.aclCheck',
'wiki.getPage',
'wiki.getPageVersion',
'wiki.getPageVersions',
'wiki.getPageInfo',
'wiki.getPageHTML',
'wiki.putPage',
'wiki.listLinks',
'wiki.getAllPages',
'wiki.getAttachments',
'wiki.getAttachmentInfo',
'plugin.acl.addAcl',
'plugin.acl.delAcl',
'plugin.xcom.getMedia'
);
