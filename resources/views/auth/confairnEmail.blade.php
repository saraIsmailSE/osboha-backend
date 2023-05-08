<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.0/dist/css/bootstrap.min.css" integrity="sha384-gH2yIJqKdNHPEq0n4Mqa/HGKIhSkIHeL5AyhkYV8i59U5AR6csBvApHHNl/vI1Bx" crossorigin="anonymous">
    <link href="https://fonts.googleapis.com/css2?family=Tajawal&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.0/dist/js/bootstrap.min.js" integrity="sha384-ODmDIVzN+pFdexxHEHFBQH3/9/vQ9uori45z4JjnFsRydbmQbmL5t1tQ0culUzyK" crossorigin="anonymous"></script>
    <title>تأكيد البريد الالكتروني</title>
</head>
<style>
    body {
        font-family: 'Tajawal', sans-serif;
    }
</style>

<body class="antialiased">

    <div class="col-sm-12 mt-3 text-center">
        <iq-card class="iq-card">
            <div class="iq-card-body p-0">
                <div class="image-block text-center">

                    <img src="{{ asset('asset/images/verified.png') }}" class="img-fluid rounded w-25" alt="blog-img">
                </div>

                <h2 class="text-center mt-3"> تم تفعيل حسابك بنجاح</h2>
                <div class="d-inline-block w-50 text-center">
                    <a href="https://www.platform.osboha180.com/auth/signin" class="btn btn-primary d-block mt-3" style="color: white; background:#278036">
                        تسجيل دخول
                    </a>
                </div>
            </div>
        </iq-card>

    </div>
</body>

</html>
