export default {
    add: (obj, event, callback) => {
        if ( obj.addEventListener ) {
            obj.addEventListener(event, callback.bind(this), false);
        } else if ( obj.attachEvent ) {
            obj.attachEvent('on' + event, callback);
        }
    },

    remove: (obj, event, callback) => {
        if ( obj.removeEventListener ) {
            obj.removeEventListener(event, callback, false);
        } else if ( obj.attachEvent ) {
            obj.detachEvent('on' + event, callback);
        }
    }
}