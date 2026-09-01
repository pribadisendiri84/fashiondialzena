<?php

namespace App\Http\Controllers\Admin;

use App\Enums\Ability;
use App\Http\Controllers\Admin\Concerns\FiltersTrashed;
use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class CategoryController extends Controller
{
    use FiltersTrashed;

    public function index(Request $request)
    {
        $query = $this->applyTrashFilter(
            Category::query()->orderBy('sort_order')->orderBy('name'),
            $request
        );

        return view('admin.categories.index', [
            'categories' => $query->get(),
            ...$this->trashViewData(Category::class, $request),
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        Category::query()->create($data);

        return redirect()->route('admin.categories.index')->with('ok', 'Kategori ditambahkan.');
    }

    public function update(Request $request, Category $category)
    {
        $category->update($this->validated($request));

        return redirect()->route('admin.categories.index')->with('ok', 'Kategori diperbarui.');
    }

    public function destroy(Category $category)
    {
        $this->authorize(Ability::DeleteRecords->value);

        if ($category->products()->withTrashed()->exists()) {
            return back()->withErrors(['Kategori masih dipakai produk. Pindahkan produk dulu.']);
        }

        $category->delete();

        return redirect()->route('admin.categories.index')->with('ok', 'Kategori dihapus. Superadmin bisa memulihkan.');
    }

    public function restore(Category $category)
    {
        $this->authorize(Ability::ManageUsers->value);
        $category->restore();

        return redirect()->route('admin.categories.index', ['trashed' => 1])->with('ok', 'Kategori dipulihkan.');
    }

    private function validated(Request $request): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:80'],
            'image_url' => ['nullable', 'url', 'max:500'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ]);

        $data['slug'] = Str::slug($data['name']);
        $data['sort_order'] = (int) ($data['sort_order'] ?? 0);

        return $data;
    }
}
