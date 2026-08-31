<x-app-layout>
    <x-slot name="header">
        <h2 class="text-2xl font-bold text-gray-800">Usuarios y Roles</h2>
    </x-slot>

    <div class="py-6 max-w-5xl mx-auto px-4">

        @if (session('success'))
            <div class="mb-4 rounded-xl bg-green-100 border border-green-300 text-green-800 p-3">
                {{ session('success') }}
            </div>
        @endif

        <form method="GET" class="mb-4">
            <input type="text" name="search" value="{{ $search }}" placeholder="Buscar por nombre o email"
                class="border-gray-300 rounded-md shadow-sm w-full md:w-1/3">
        </form>

        <div class="bg-white rounded-xl shadow overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Nombre</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Email</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Roles</th>
                        <th class="px-4 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @forelse ($users as $user)
                        <tr>
                            <td class="px-4 py-3">{{ $user->name }}</td>
                            <td class="px-4 py-3">{{ $user->email }}</td>
                            <td class="px-4 py-3">
                                @forelse ($user->roles as $role)
                                    <span class="inline-block bg-gray-100 text-gray-700 text-xs px-2 py-1 rounded-full mr-1 mb-1">
                                        {{ $role->name }}
                                    </span>
                                @empty
                                    <span class="text-xs text-gray-400">Sin rol asignado</span>
                                @endforelse
                            </td>
                            <td class="px-4 py-3 text-right">
                                <a href="{{ route('admin.usuarios.edit', $user) }}"
                                    class="text-indigo-600 hover:text-indigo-800 text-sm font-medium">
                                    Editar
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-4 py-6 text-center text-gray-400">No hay usuarios.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-4">
            {{ $users->links() }}
        </div>
    </div>
</x-app-layout>