var AdviceListObj = new AdviceList();
     
function AdviceList() {

    // Singletone template

    if (arguments.callee._singletonInstance)
        return arguments.callee._singletonInstance;
    arguments.callee._singletonInstance = this;

    var base = null;
    var advicePool = [];
    var itemId = 'content-advice';

    this.show = function() {
        var advice = '';

        if (!advicePool.length) return;
        
        advice = advicePool[Math.floor(Math.random() * advicePool.length)];
        
        var element = document.getElementById(itemId);
        
        if (base === null) base = element.innerHTML;
        
        element.innerHTML = base + advice;
        element.style.display = 'block';
    };

    this.add = function(advice) {
        advicePool.push(advice);
    };
}