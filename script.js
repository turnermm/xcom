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
     'dokuwiki_getPagelist':  xcom_thead('id','rev', 'mtime' ,'size'),
     'wiki_getPageVersions': xcom_thead('user','ip','type','sum','modified','version' ),
     'wiki_getPageInfo': xcom_thead('name','lastModified','author','version' ),
     'wiki_getAllPages': xcom_thead('id', 'perms', 'size', 'lastModified'),
     'dokuwiki_search': xcom_thead('id', 'score', 'rev', 'mtime','size'),
     'plugin_xcom_getMedia': xcom_thead('Media files'),
   };
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
                        data = table_calls[fncall]; 
                         if(fn == 'wiki.getPageInfo') {
                                data +=  xcom_twodim(obj);
                           }     
                          else if (fn == 'plugin.xcom.getMedia') {
                               data +=  xcom_onedim(obj);
                           }                          
                          else {
                               data+=xcom_multidim(obj,fn);
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
}

function xcom_multidim(obj,func) {
        var data = "";
        
        for(var i in obj) {      
         data +="\n<tr>";                                                        
         for(var j in obj[i]) {                                 
             var r = obj[i][j];
             data += xcom_td(j,r,func);            
         } 
       }
       
       return data;
}

function xcom_twodim(obj) {
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

function xcom_thead() {
  var row = "<table class ='xcom_center'>\n<tr>";
  for (i=0; i<arguments.length; i++) {
     row += '<th>' + arguments[i] + '</th>';
  }
   return row + "</tr>\n";
}

function xcom_td(type,val,fn) {

    if(type == 'modified' || type == 'lastModified' && typeof val == 'object') {    
        var min =val['minute'] ?  val['minute'] : val['minut'];
        var d = new Date( val['year'],val['month'],val['day'],val['hour'],val['minute'], val['second']);
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
            params[++i] = opts[j]; 
          }
    }
    fn_sel.selectedIndex = 0;
    return params; 
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

function xcom_select(t) {
//alert(t.selectedIndex + " \n" + t.options[t.selectedIndex].value);
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
       for(i=0; i<xcom_opts.length; i++) {
           var text = xcom_opts[i].match(/^plugin\./) ? xcom_opts[i].replace(/^plugin\./,"") : (xcom_opts[i].split('.'))[1]; 
            var newopt = new Option(text,xcom_opts[i]);
            newopt.title = xcom_opts[i];
            sel.add(newopt);
           //sel.add(new Option(text,xcom_opts[i]));
       }
       var ini = { 'xcom_user': 'rpcuser', 'xcom_pwd': 'rpcpwd', 'xcom_url': 'http://192.168.0.77/adora'};        
        for (var key in ini) {  
           xcom_setValue(key,ini[key]);        
           }
       }
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
'wiki.putAttachment',
'plugin.acl.addAcl',
'plugin.acl.delAcl',
'plugin.xcom.getMedia'
);