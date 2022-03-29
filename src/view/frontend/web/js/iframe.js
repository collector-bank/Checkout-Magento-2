define([
], function () {
    'use strict';

    var currentState = '';
    
    function suspend() {
        if (currentState == '' || currentState== 'resumed') {
            window.collector.checkout.api.suspend();
            console.log("suspended");
            currentState = 'suspended';
        }
    };
    function resume() {
                if (currentState == '' || currentState== 'suspended') {
            window.collector.checkout.api.resume();
            console.log("resumed");
            currentState = 'resumed';
        }
    };

    return {
        suspend: suspend,
        resume: resume,
    };
});