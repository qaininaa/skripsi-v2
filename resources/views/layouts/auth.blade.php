<!DOCTYPE html>
<html class="h-full bg-white" lang="en">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>@yield('title', 'Auth')</title>
	@vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="h-full">
	<div class="flex min-h-full">
		<div class="hidden lg:block lg:w-1/2 relative">
			<img src="{{ asset('images/loginImage.png') }}" alt="Login Image" class="absolute inset-0 h-full w-full object-cover">
			<div class="absolute inset-0 bg-indigo-900/20 mix-blend-multiply"></div>
		</div>

		<div class="flex flex-1 flex-col justify-center px-6 py-12 lg:w-1/2 lg:px-24">
			<div class="sm:mx-auto sm:w-full sm:max-w-sm">
				<h2 class="mt-10 text-center text-2xl/9 font-bold tracking-tight text-gray-900">
					@yield('auth-heading', 'Masuk ke Akun Anda')
				</h2>
			</div>

			<div class="mt-10 sm:mx-auto sm:w-full sm:max-w-sm">
				@if (session('status'))
					<div class="mb-4 text-sm font-medium text-green-600">
						{{ session('status') }}
					</div>
				@endif

				@yield('auth-content')
			</div>
		</div>
	</div>
</body>
</html>
