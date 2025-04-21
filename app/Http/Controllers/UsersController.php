<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;
use App\Models\User;
use App\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class UsersController extends Controller
{
    use AuthorizesRequests, ValidatesRequests;

    public function getUsersTable(Request $request)
    {
        $page = $request->input('page', 0); 
        $row = $request->input('row', 10);
        $search = $request->input('search', '');
        $orderBy = $request->input('order_by', 'created_at');
        $order = $request->input('order', 'DESC');
        $params = $request->input('params');
        $orderBy = match ($orderBy) {
            'createdAt' => 'created_at',
            'updatedAt' => 'updated_at',
            'role' => 'roles.name',
            default => $orderBy
        };
        $query = User::with(['roles' => function ($q) {
            $q->select('roles.id', 'roles.name');
        }]);
        if ($orderBy === 'roles.name') {
            $query->leftJoin('role_user', 'users.id', '=', 'role_user.user_id')
                  ->leftJoin('roles', 'roles.id', '=', 'role_user.role_id');
        }
        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%$search%")
                    ->orWhere('email', 'like', "%$search%");
                // Buscar por estado activo/inactivo
                if (strtolower($search) === 'activo' || $search === '1') {
                    $q->orWhere('active', 1);
                } elseif (strtolower($search) === 'inactivo' || $search === '2') {
                    $q->orWhere('active', 2);
                }
                // Buscar por nombre del rol
                $q->orWhereHas('roles', function ($qr) use ($search) {
                    $qr->where('name', 'like', "%$search%");
                });
            });
        }
        if (!empty($params) && is_array($params)) {
            foreach ($params as $key => $value) {
                if (!empty($value)) {
                    $query->where($key, $value);
                }
            }
        }
        $query->orderBy($orderBy, $order);
        $results = $query->select('users.*')->paginate($row, ['*'], 'page', $page + 1);
        $formatted = $results->items();
        $data = array_map(function ($user) {
            return [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'active' => $user->active,
                'role' => $user->roles->first()?->name ?? null,
                'created_at' => $user->created_at,
            ];
        }, $formatted);
        return response()->json([
            'data' => [
                'content' => $data,
                'totalElements' => $results->total()
            ]
        ]);
    }

    public function getListsRoles(Request $request): JsonResponse
    {
        try {
            $roles = Role::select('id', 'name', 'description', 'active')->get();
            return response()->json([
                'status' => 200,
                'message' => 'Lista de roles obtenida correctamente.',
                'data' => [
                    'content' => $roles,
                    'totalElements' => $roles->count()
                ],
                'error' => false
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'status' => 500,
                'message' => 'Error al obtener los roles.',
                'data' => null,
                'error' => true
            ], 500);
        }
    }

    public function saveUser(Request $request)
    {
        $isEdit = !empty($request->id);
        // Validación de los datos
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($request->id),
            ],
            'active' => 'required|numeric|in:1,2',
            'password' => 'nullable|string|min:6',
            'role' => 'required|array|exists:roles,id',
        ]);
        try {
            if ($isEdit) {
                $user = User::findOrFail($request->id); 
            } else {
                $user = new User();
            }
            // Asignar los datos
            $user->name = $request->name;
            $user->email = $request->email;
            $user->active = $request->active;
            if ($request->password) {
                $user->password = Hash::make($request->password);
            }
            $user->save();
            $user->roles()->sync(is_array($request->role) ? $request->role : [$request->role]);
            return response()->json([
                'status' => 200,
                'message' => $isEdit ? 'Usuario actualizado con éxito' : 'Usuario creado con éxito',
                'data' => $user,
                'error' => false
            ]);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'status' => 404,
                'message' => 'Usuario no encontrado',
                'error' => true
            ]);
        }
    }

    public function getUserById($id): JsonResponse
    {
        $user = User::with(['roles' => function ($query) {
            $query->select('roles.id', 'roles.name');
        }])->find($id);
        if (!$user) {
            return response()->json([
                'status' => 404,
                'message' => 'Usuario no encontrado',
                'data' => null,
                'error' => true
            ]);
        }
        $user->roles->each(function ($role) {
            $role->makeHidden('pivot');
        });
        return response()->json([
            'status' => 200,
            'message' => 'Usuario encontrado',
            'data' => $user,
            'error' => false
        ]);
    }

    public function deleteUser($id)
    {
        try {
            $user = User::findOrFail($id);
            $user->delete();
            return response()->json([
                'status' => 200,
                'message' => 'Usuario eliminado con éxito',
                'error' => false
            ]);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'status' => 404,
                'message' => 'Usuario no encontrado',
                'error' => true
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 500,
                'message' => 'Error al eliminar el usuario',
                'error' => true
            ]);
        }
    }

}

