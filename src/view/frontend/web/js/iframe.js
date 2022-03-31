define([
], function () {
    'use strict';
    
    // Make sure we dont do any resumes before user done a action that sends a suspend
    var suspendActionHaveBeenTriggered = false;
    
    function suspend() {
            window.collector.checkout.api.suspend();
            suspendActionHaveBeenTriggered = true;
    };
    function resume() {
         if (suspendActionHaveBeenTriggered === true) {
            window.collector.checkout.api.resume();
         }
    };

    return {
        suspend: suspend,
        resume: resume,
    };
});