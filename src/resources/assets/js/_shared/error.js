import EDAPI from 'ed-api';

const IgnoreList = [ 
    /**
     * This particular exception is excluded as this is a third-party component, and not
     * something which would be raised by our code.
     */
    'GoogleDocsResearchGsaProxy',
    'document.getElementsByClassName.ToString', // Samsung Internet bug (https://github.com/SamsungInternet/support/issues/56)
    'at <anonymous>:' // we do not have anonymous scripts, so this is probably an extension.
];

(function () {
    const originalFunc = window.onerror;

    window.onerror = function (message, source, lineNumber, columnNumber, error) {
        const string = message.toLowerCase();
        const disqualified = "script error";

        if (string.indexOf(disqualified) === -1) {
            const message = `${message} (${source}:${lineNumber}:${columnNumber})`;
            const url = window.location.href;
            const stack = `${navigator.appName} ${navigator.appVersion}\n${error ? error.stack : ''}`;
            
            if (IgnoreList.every(ignore => stack.indexOf(ignore) === -1)) {
                EDAPI.error(message, url, stack);
            }
        }

        if (typeof originalFunc === 'function') {
            originalFunc(message, source, lineNumber, columnNumber, error);
        }

        return false;
    };
}());
