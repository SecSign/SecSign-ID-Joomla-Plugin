
/*!
 * This script contains general helper functions.
 * components menu of the back end is selected.
 *
 * @copyright    Copyright (C) 2014 - 2016 SecSign Technologies Inc. All rights reserved.
 * @license      GNU General Public License version 2 or later; see LICENSE.txt.
 */


//responsive layout
window.onload = function () {
    var width = document.getElementById("secsignidplugin").offsetWidth;
    responsive(width);
};

window.addEventListener('resize', function () {
    var width = document.getElementById("secsignidplugin").offsetWidth;
    responsive(width);
});

function responsive(width) {
    console.log('check responsive layout');
    if (width >= 250) {
        jQuery("#secsignidplugin").removeClass("miniview");
        jQuery("#secsignid-accesspass-container").removeClass("miniview");
        jQuery("#secsignid-accesspass-img").removeClass("miniview");
        jQuery("#secsignidplugin").css("padding","30px");
    } else {
        jQuery("#secsignidplugin").addClass("miniview");
        jQuery("#secsignid-accesspass-container").addClass("miniview");
        jQuery("#secsignid-accesspass-img").addClass("miniview");
        jQuery("#secsignidplugin").css("padding","15px");

    }
}

//helper for clearing all input fields
function clearSecsignForm() {
    jQuery("#secsignid-accesspass-img").attr('src', secsignPluginPath+'images/preload.gif');
    jQuery("#secsignidplugin").find("input[type='text']").val("");
    jQuery("#secsignid-error").html("").css('display', 'none');
    //get Rememberme Cookie
    secsignid = docCookies.getItem('secsignRememberMe');
    if (secsignid) {
        jQuery("input[name='secsigniduserid']").val(secsignid);
    }
}


// Cookie handling for remember me checkbox and backend secsign/password login
var docCookies = {
    getItem: function (sKey) {
        if (!sKey) { return null; }
        return decodeURIComponent(document.cookie.replace(new RegExp("(?:(?:^|.*;)\\s*" + encodeURIComponent(sKey).replace(/[\-\.\+\*]/g, "\\$&") + "\\s*\\=\\s*([^;]*).*$)|^.*$"), "$1")) || null;
    },
    setItem: function (sKey, sValue, vEnd, sPath, sDomain, bSecure) {
        if (!sKey || /^(?:expires|max\-age|path|domain|secure)$/i.test(sKey)) { return false; }
        var sExpires = "";
        if (vEnd) {
            switch (vEnd.constructor) {
                case Number:
                    sExpires = vEnd === Infinity ? "; expires=Fri, 31 Dec 9999 23:59:59 GMT" : "; max-age=" + vEnd;
                    break;
                case String:
                    sExpires = "; expires=" + vEnd;
                    break;
                case Date:
                    sExpires = "; expires=" + vEnd.toUTCString();
                    break;
            }
        }
        document.cookie = encodeURIComponent(sKey) + "=" + encodeURIComponent(sValue) + sExpires + (sDomain ? "; domain=" + sDomain : "") + (sPath ? "; path=" + sPath : "") + (bSecure ? "; secure" : "");
        return true;
    },
    removeItem: function (sKey, sPath, sDomain) {
        if (!this.hasItem(sKey)) { return false; }
        document.cookie = encodeURIComponent(sKey) + "=; expires=Thu, 01 Jan 1970 00:00:00 GMT" + (sDomain ? "; domain=" + sDomain : "") + (sPath ? "; path=" + sPath : "");
        return true;
    },
    hasItem: function (sKey) {
        if (!sKey) { return false; }
        return (new RegExp("(?:^|;\\s*)" + encodeURIComponent(sKey).replace(/[\-\.\+\*]/g, "\\$&") + "\\s*\\=")).test(document.cookie);
    },
    keys: function () {
        var aKeys = document.cookie.replace(/((?:^|\s*;)[^\=]+)(?=;|$)|^\s*|\s*(?:\=[^;]*)?(?:\1|$)/g, "").split(/\s*(?:\=[^;]*)?;\s*/);
        for (var nLen = aKeys.length, nIdx = 0; nIdx < nLen; nIdx++) { aKeys[nIdx] = decodeURIComponent(aKeys[nIdx]); }
        return aKeys;
    }
};