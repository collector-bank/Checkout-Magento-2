define([
], function () {
    'use strict';
    
    // Make sure we dont do any resumes before user done a action that sends a suspend
    var suspendActionHaveBeenTriggered = false;
    
    function suspend() {
            window.collector.checkout.api.suspend();
            console.log("suspended");
            suspendActionHaveBeenTriggered = true;
    };
    function resume() {
         if (suspendActionHaveBeenTriggered === true) {
            window.collector.checkout.api.resume();
            console.log("resumed");
         }
    };

    return {
        suspend: suspend,
        resume: resume,
    };
});