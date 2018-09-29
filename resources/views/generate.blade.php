<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="utf-8">
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <link href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css" rel="stylesheet">
        <title>generate</title>
    </head>
    <body>
        <div class="container">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>卡密</th>
                        <th>时长</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($keys as $key)
                    <tr>
                        <td>{{$key['key']}}</td>
                        <td>{{$key['times']}}</td>
                    </tr>
                    @endforeach    
                </tbody>
            </table>
        </div>
    </body>
</html>
