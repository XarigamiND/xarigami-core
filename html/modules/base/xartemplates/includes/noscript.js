addEvent(window,'load',noscript); 

function noscript()
 { 
    if ((document.getElementById("js_noscript")) && ( document.getElementById("js_noscript"))) {
        if (document.removeChild)  {
           var div = document.getElementById("js_noscript");
               div.parentNode.removeChild(div);
               document.getElementById("js_usescript").style.display = "inline";
        } else if (document.getElementById) {
           document.getElementById("js_noscript").style.display = "none";
        }
    } 
 }
