<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tambah Users</title>
</head>
<body>
    <form action="{{ route('users.store') }}" method="POST">
        @csrf
        <label for="username">Username:</label>
        <input type="text" id="username" name="username" required>
        <label for="name">Name:</label>
        <input type="text" id="name" name="name" required>
        <label for="password">Password:</label>
        <input type="password" id="password" name="password" required>
        <label for="role">Role:</label>
        <select id="role" name="role" required>
            <option value="super">Super</option>
            <option value="admin">Admin</option>
            <option value="analyst">Analyst</option>
            <option value="supervisor">Supervisor</option>
            <option value="manager">Manager</option>
        </select>
        <button type="submit">Tambah User</button>
    </form>
</body>
</html>