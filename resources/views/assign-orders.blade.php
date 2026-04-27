<form method="POST" action="{{ route('assign.orders') }}">
    @csrf

    <label>Select Client:</label>
    <select name="client_id" required>
        @foreach ($clients as $client)
            <option value="{{ $client->id }}">{{ $client->client_name }}</option>
        @endforeach
    </select>

    <br><br>

    <label>Select Staff:</label><br>
    @foreach ($staffs as $staff)
        <input type="checkbox" name="staff_ids[]" value="{{ $staff->id }}">
        {{ $staff->name }} <br>
    @endforeach

    <br>
    <button type="submit">Assign Orders</button>
</form>
