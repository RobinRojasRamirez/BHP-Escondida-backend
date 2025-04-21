<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;
use App\Models\User;
use App\Models\Role;
use App\Models\Instruction;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class InstructionsController extends Controller
{
    use AuthorizesRequests, ValidatesRequests;

    public function getInstructionsTable(Request $request)
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
            default => $orderBy
        };

        $query = Instruction::query()->whereNull('deleted_at');;
   
        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%$search%")
                    ->orWhere('url', 'like', "%$search%");
                // Buscar por estado activo/inactivo
                if (strtolower($search) === 'activo' || $search === '1') {
                    $q->orWhere('active', 1);
                } elseif (strtolower($search) === 'inactivo' || $search === '2') {
                    $q->orWhere('active', 2);
                }
                
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
        $results = $query->select('instructions.*')->paginate($row, ['*'], 'page', $page + 1);
        $formatted = $results->items();
        $data = array_map(function ($user) {
            return [
                'id' => $user->id,
                'name' => $user->name,
                'url' => $user->url,
                'active' => $user->active,
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

    public function saveInstruction(Request $request)
    {
        $isEdit = !empty($request->id);
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'url' => 'required|string',
            'active' => 'required|numeric|in:1,2',
            'user_id' => 'required|integer|exists:users,id',
        ]);
        try {
            if ($isEdit) {
                $instruction = Instruction::findOrFail($request->id);
            } else {
                $instruction = new Instruction();
                $instruction->user_id = $request->user_id;
            }
            $instruction->name = $request->name;
            $instruction->url = $request->url;
            $instruction->active = $request->active;
            $instruction->save();
            return response()->json([
                'status' => $isEdit ? 200 : 201,
                'message' => $isEdit ? 'Instructivo actualizado con éxito' : 'Instructivo creado con éxito',
                'data' => $instruction,
                'error' => false
            ], $isEdit ? 200 : 201); 
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'status' => 404,
                'message' => 'Instructivo no encontrado',
                'error' => true
            ], 404);
        }
    }

    public function getInstructionById($id): JsonResponse
    {
        $instruction = Instruction::find($id);
        if (!$instruction) {
            return response()->json([
                'status' => 404,
                'message' => 'Instructivo no encontrado',
                'data' => null,
                'error' => true
            ]);
        }
        return response()->json([
            'status' => 200,
            'message' => 'Instructivo encontrado',
            'data' => $instruction,
            'error' => false
        ]);
    }

    public function deleteInstruction($id)
    {
        try {
            $instruction = Instruction::findOrFail($id);
            $instruction->delete();
            return response()->json([
                'status' => 200,
                'message' => 'Instructivo eliminado con éxito',
                'error' => false
            ]);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'status' => 404,
                'message' => 'Instructivo no encontrado',
                'error' => true
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 500,
                'message' => 'Error al eliminar el Instructivo',
                'error' => true
            ]);
        }
    }

}

