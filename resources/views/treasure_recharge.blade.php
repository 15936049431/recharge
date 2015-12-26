<!DOCTYPE html>
<html>
    <head>
        <title>Laravel</title>

        

        <style>
            html, body {
                height: 100%;
            }

            body {
                margin: 0;
                padding: 0;
                width: 100%;
                display: table;
                font-weight: 100;
                font-family: 'Lato';
            }

            .container {
                text-align: center;
                display: table-cell;
                vertical-align: middle;
            }

            .content {
                text-align: center;
                display: inline-block;
            }

            .title {
                font-size: 68px;
            }
        </style>
    </head>
    <body>
        <div class="container">
            <div class="content">
                <div class="title">正在跳转中</div>
				<div class="title">请稍候......</div>
				<form action="{{ $treasure_url }}" name="form1" method="post">
				@foreach($treasure_submit as $k=>$v)
					<input type="hidden" name="{{ $k }}" value="{{ $v }}" />
				@endforeach
				</form>
				<script>
					document.form1.submit();
				</script>
            </div>
        </div>
    </body>
</html>
