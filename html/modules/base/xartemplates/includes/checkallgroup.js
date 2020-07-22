/**
 * checkbox toggle to check or uncheck all checkboxes in a form in a particular group
 * Example : checklist is the name of the group to check
 * <input type="checkbox" id="selectall"  onclick="xar_base_checkallgroup(document.forms['comment-review'],this,'checklist');return true;" />
 * <input type="checkbox" id="checkall[#$aid#]" name="checkall[#$aid#]" />
 */

 function xar_base_checkallgroup(formobject, value, thisname) { 

   var tester = new RegExp(thisname);

    for (i = 0; i < formobject.length; i++) {
        if (formobject.elements[i].type == 'checkbox') {
       
            if (tester.test(formobject.elements[i].id)) {
               formobject.elements[i].checked =value.checked ? true : false ;        
            } else {
 
            }
        }
    }
}
