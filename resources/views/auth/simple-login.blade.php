<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - PCM System</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 min-h-screen flex items-center justify-center">
    <div class="max-w-md w-full bg-white rounded-lg shadow-md p-6">
        <div class="text-center mb-6">
            <h1 class="text-2xl font-bold text-gray-800">PCM System</h1>
            <p class="text-gray-600">Silakan login untuk melanjutkan</p>
        </div>

        @if($errors->any())
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                @foreach($errors->all() as $error)
                    <p>{{ $error }}</p>
                @endforeach
            </div>
        @endif

        <form method="POST" action="{{ route('login.post') }}">
            @csrf
            
            <div class="mb-4">
                <label for="email" class="block text-gray-700 text-sm font-bold mb-2">
                    Email Address
                </label>
                <input type="email" 
                       name="email" 
                       id="email"
                       value="adhe5381@gmail.com"
                       required 
                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>

            <div class="mb-6">
                <label for="password" class="block text-gray-700 text-sm font-bold mb-2">
                    Password
                </label>
                <input type="password" 
                       name="password" 
                       id="password"
                       value="membanguN5381"
                       required
                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>

            <button type="submit" 
                    class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline mb-3">
                Login
            </button>

            <a href="{{ route('login') }}" 
               class="w-full bg-green-600 hover:bg-green-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline block text-center">
                Quick Login (Admin)
            </a>
        </form>

        <div class="mt-6 p-4 bg-gray-50 rounded">
            <h3 class="font-bold text-gray-700 mb-2">Test Account:</h3>
            <div class="text-sm text-gray-600">
                <div><strong>Email:</strong> adhe5381@gmail.com</div>
                <div><strong>Password:</strong> membanguN5381</div>
                <div><strong>Role:</strong> Administrator</div>
            </div>
        </div>
    </div>
</body>
</html>
