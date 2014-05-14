<script>
    var ident = '{(!empty($ident)) ? $ident : $base}';
    var _v = _v || []; _v.push(
            ['ti', {$orderId}],
            ['os', {$orderSum}],
            ['pl', '{$productListing}'],
            ['oc', {$orderCost}],
            ['ident', ident],
            ['test', {$test}]
    );
    var _a = "/pot/?v=2.1&p=" + encodeURIComponent(_v) + "&_=" + (Math.random() + "" * 10000000000000),
            _p = ('https:' == document.location.protocol ? 'https://' : 'http://'),
            _i = new Image;
    _i.onerror = function(e) { _i.src = _p+"\x70\x32\x2E\x62\x65\x73\x6C\x69\x73\x74\x2E\x6E\x6C"+_a; _i = false; }; _i.src = _p+"\x77\x77\x77\x2E\x62\x65\x73\x6C\x69\x73\x74\x2E\x6E\x6C"+_a;
</script>