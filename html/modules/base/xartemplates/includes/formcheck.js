/***********************************************
* Required field(s) validation v1.10- By NavSurf
* Visit Nav Surf at http://navsurf.com
* Visit http://www.dynamicdrive.com/ for full source code
***********************************************/

// optional args: (fieldRequired, fieldDescription), alertMsg
function xar_base_formCheck(formobj, fieldRequired, fieldDescription, alertMsg){
    // Use the default alert message if nothing is given
    if (alertMsg == null) {
        alertMsg = "Please complete the following fields:";
    } 
    alertMsg += "\n";
    
    var l_Msg = alertMsg.length;
    
    // Do we have an array with fields to validate?
    if (typeof fieldRequired != 'undefined') {
      for (var i = 0; i < fieldRequired.length; i++){
        var obj = formobj.elements[fieldRequired[i]];
        if (obj){
            switch(obj.type){
            case "checkbox":
                if (!obj.checked){
                    alertMsg += " - " + fieldDescription[i] + "\n";
                }
                break;
            case "select-one":
                if (obj.selectedIndex == -1 || obj.options[obj.selectedIndex].text == ""){
                    alertMsg += " - " + fieldDescription[i] + "\n";
                }
                break;
            case "select-multiple":
                if (obj.selectedIndex == -1){
                    alertMsg += " - " + fieldDescription[i] + "\n";
                }
                break;
            case "text":
            case "textarea":
                if (obj.value == "" || obj.value == null){
                    alertMsg += " - " + fieldDescription[i] + "\n";
                }
                break;
            default:
            }
            if (obj.type == undefined){
                var blnchecked = false;
                for (var j = 0; j < obj.length; j++){
                    if (obj[j].checked){
                        blnchecked = true;
                    }
                }
                if (!blnchecked){
                    alertMsg += " - " + fieldDescription[i] + "\n";
                }
            }
        }
      }
    }

    // Is the message still the same?
    if (alertMsg.length == l_Msg){
        /***********************************************
        * Submit Once form validation- © Dynamic Drive (http://www.dynamicdrive.com)
        * This notice MUST stay intact for legal use
        * Visit http://www.dynamicdrive.com/ for this script and 100s more.
        ***********************************************/
        //if IE 4+ or NS 6+
        if (document.all||document.getElementById){
            //screen thru every element in the form, and hunt down "submit" and "reset"
            for (i=0; i<formobj.length; i++) {
                var tempobj=formobj.elements[i];
                if (tempobj.type.toLowerCase() == "submit" ||
                    tempobj.type.toLowerCase() == "reset") {
                    //disable em
                    tempobj.disabled=true;
                }
            }
        }
        return true;
    }else{
        alert(alertMsg);
        return false;
    }
}