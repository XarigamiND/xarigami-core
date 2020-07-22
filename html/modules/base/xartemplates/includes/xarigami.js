var jsReady = document.getElementsByTagName && document.createElement && document.createTextNode && document.documentElement && document.getElementById;
/**
 * Check to see if javascript is ready and load basic event loader
 */

function addEvent(obj, evType, fn){ 
    if (obj.addEventListener){ 
       obj.addEventListener(evType, fn, false); 
       return true; 
    } else if (obj.attachEvent){ 
        var r = obj.attachEvent('on'+evType, fn); 
        return r; 
    } else { 
        return false; 
    } 
}



