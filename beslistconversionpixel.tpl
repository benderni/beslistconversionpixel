<script>
    var beslistQueue = [];
    beslistQueue.push(['setShopId', '{$ident}']);
    {if $test == 1}
    beslistQueue.push(['cps', 'setTestmode', true]);
    {/if}
    beslistQueue.push(['cps', 'setTransactionId', {$orderId}]);
    beslistQueue.push(['cps', 'setOrdersum', {$orderSum}]);
    beslistQueue.push(['cps', 'setOrderCosts', {$orderCost}]);
    beslistQueue.push(['cps', 'setOrderProducts',[
        {foreach from=$productListing item=v}
            [{$v['id']}, {$v['qty']}, {$v['price']}],
        {/foreach}
    ]]);
    beslistQueue.push(['cps', 'trackSale']);
    (function () {
        var ba = document.createElement('script');
        ba.async = true;
        ba.src = '//pt1.beslist.nl/pt.js';
        var s = document.getElementsByTagName('script')[0];
        s.parentNode.insertBefore(ba, s);
    })();
</script>