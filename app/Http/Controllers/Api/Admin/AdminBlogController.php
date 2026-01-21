<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Blog;
use App\Models\Comment;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;


class AdminBlogController extends Controller
{
    public function index()
    {
        try {
            $blog = Blog::with('category:id,category_name')->paginate(10);

            return response()->json($blog, 200);
        } catch (Exception $ex) {
            Log::error($ex->getMessage());
            return response()->json(['message' => 'An unexpected error occurred'], 500);
        }
    }

    public function edit($slug)
    {
        try {
            $blog = Blog::with(['postTags', 'category'])->where('slug', $slug)->first();

            if (!$blog) {
                return response()->json(['message' => 'Post Not Found!'], 404);
            }

            return response()->json($blog, 200);
        } catch (Exception $ex) {
            Log::error($ex->getMessage());
            return response()->json(['message' => 'An unexpected error occurred'], 500);
        }
    }

    public function update(Request $request, $slug)
    {
        Log::info($request->featured_image);
        // Validation logic here (similar to store method)
        $request->validate([
            'title' => ['required', 'string'],
            'category' => ['required'],
            'tags' => ['nullable', 'array'],
            'tags.*' => ['string'],
            'content' => ['required', 'string'],
            'status' => ['required', 'in:draft,published'],
            'featured_image' => ['nullable', 'file', 'mimes:jpg,jpeg,webp,png'],
            'comments_enabled' => ['required', 'in:0,1'],
            'published_at' => ['nullable']
        ], [
            'title.required' => 'This field is required',
            'title.string' => 'Invalid input',

            'category.required' => 'This field is required',

            'tags.required' => 'This field is required',
            'tags.array' => 'Invalid format',
            'tags.string' => 'Invalid input',

            'content.required' => 'This field is required',
            'content.string' => 'Invalid input',

            'status.required' => 'This field is required',
            'status.in' => 'Invalid status type',

            'featured_image.required' => 'This field is required',
            'featured_image.mimes' => 'Invalid image',

            'comments_enabled.required' => 'This field is required'
        ]);

        DB::beginTransaction();

        try {
            $blog = Blog::where('slug', $slug)->first();

            if (!$blog) {
                return response()->json(['message' => 'Post Not Found!'], 404);
            }

            // Update basic fields
            $blog->fill([
                'title' => $request->title,
                'category' => (string) $request->category,
                'content' => $request->content,
                'status' => $request->status,
                'comments_enabled' => (bool) $request->comments_enabled,
                'published_at' => $request->published_at,
            ]);

            // ✅ Handle featured image only if a new file is uploaded
            if ($request->hasFile('featured_image')) {
                $file      = $request->file('featured_image');
                $directory = 'blogs';
                $filename  = uniqid() . '_' . $file->getClientOriginalName();

                if (!Storage::disk('public')->exists($directory)) {
                    Storage::disk('public')->makeDirectory($directory);
                }

                // Delete old image if it exists
                if ($blog->featured_image && Storage::disk('public')->exists($blog->featured_image)) {
                    Storage::disk('public')->delete($blog->featured_image);
                }

                // Upload new image
                Storage::disk('public')->putFileAs($directory, $file, $filename);
                $blog->featured_image = $directory . '/' . $filename;
            }

            // Update only if there are changes
            if ($blog->isDirty()) {
                $blog->save();
            }

            // Update tags if changed
            if ($request->filled('tags')) {
                $tagsJson = json_encode($request->tags);
                if ($blog->postTags()->first()?->tag !== $tagsJson) {
                    $blog->postTags()->update(['tag' => $tagsJson]);
                }
            }

            DB::commit();

            return response()->json(['message' => 'Post updated successfully'], 200);
        } catch (Exception $ex) {
            DB::rollBack();
            Log::error($ex->getMessage());
            return response()->json(['message' => 'An unexpected error occurred'], 500);
        }
    }

    public function view($slug)
    {
        try {
            $blog = Blog::with(['category:id,category_name', 'comment'])
                ->where('slug', $slug)
                ->first();

            if (!$blog) {
                return response()->json(['message' => 'Post Not Found!'], 404);
            }

            // Get previous post (by created_at)
            $previous = Blog::where('created_at', '<', $blog->created_at)
                ->orderBy('created_at', 'desc')
                ->select('id', 'title', 'slug', 'featured_image')
                ->first();

            // Get next post (by created_at)
            $next = Blog::where('created_at', '>', $blog->created_at)
                ->orderBy('created_at', 'asc')
                ->select('id', 'title', 'slug', 'featured_image')
                ->first();

            return response()->json([
                'blog' => $blog,
                'previous' => $previous,
                'next' => $next,
            ], 200);
        } catch (Exception $ex) {
            Log::error($ex->getMessage());
            return response()->json(['message' => 'An unexpected error occurred'], 500);
        }
    }


    public function store(Request $request)
    {
        $request->validate([
            'title' => ['required', 'string'],
            'category' => ['required'],
            'tags' => ['nullable', 'array'],
            'tags.*' => ['string'],
            'content' => ['required', 'string'],
            'status' => ['required', 'in:draft,published'],
            'featured_image' => ['required', 'mimes:jpg,jpeg,webp,png'],
            'comments_enabled' => ['required', 'in:0,1'],
            'published_at' => ['nullable']
        ], [
            'title.required' => 'This field is required',
            'title.string' => 'Invalid input',

            'category.required' => 'This field is required',

            'tags.required' => 'This field is required',
            'tags.array' => 'Invalid format',
            'tags.string' => 'Invalid input',

            'content.required' => 'This field is required',
            'content.string' => 'Invalid input',

            'status.required' => 'This field is required',
            'status.in' => 'Invalid status type',

            'featured_image.required' => 'This field is required',
            'featured_image.mimes' => 'Invalid image',

            'comments_enabled.required' => 'This field is required'
        ]);

        DB::beginTransaction();

        $uploadedFilePath = null;

        try {
            // Generate unique slug
            $slug = Str::slug($request->title);
            $originalSlug = $slug;
            $count = 1;

            while (Blog::where('slug', $slug)->exists()) {
                $slug = $originalSlug . '-' . $count++;
            }

            // Create blog
            $blog = Blog::create([
                'title'            => $request->title,
                'slug'             => $slug,
                'category'         => (string) $request->category,
                'content'          => $request->content,
                'status'           => $request->status,
                'comments_enabled' => (bool) $request->comments_enabled,
                'published_at'     => $request->published_at,
            ]);

            // Save tags as JSON array
            // Save tags as JSON array
            if ($request->filled('tags')) {
                $blog->postTags()->create([
                    'tag' => json_encode($request->tags), // ✅ encode array to JSON
                ]);
            }


            // ✅ Handle featured image
            if ($request->hasFile('featured_image')) {
                $file      = $request->file('featured_image');
                $directory = 'blogs'; // goes into storage/app/public/blogs
                $filename  = uniqid() . '_' . $file->getClientOriginalName();

                // ✅ Ensure directory exists
                if (!Storage::disk('public')->exists($directory)) {
                    Storage::disk('public')->makeDirectory($directory);
                }

                // Store file
                Storage::disk('public')->putFileAs($directory, $file, $filename);

                $uploadedFilePath = $directory . '/' . $filename;

                // Save path in DB
                $blog->update(['featured_image' => $uploadedFilePath]);
            }


            DB::commit();

            return response()->json(['message' => 'Post saved successfully'], 200);
        } catch (\Exception $ex) {
            DB::rollBack();

            // 🚨 If file was uploaded, delete it
            if ($uploadedFilePath) {
                Storage::disk('public')->delete($uploadedFilePath);
            }

            Log::error($ex->getMessage());

            return response()->json(['message' => 'An unexpected error occurred'], 500);
        }
    }

    public function destroyComment($id)
    {
        try {
            $comment = Comment::find($id);

            if (!$comment) {
                return response()->json(['message' => 'Comment was not found'], 404);
            }

            $comment->delete();

            return response()->json(['message' => 'Comment deleted successfully'], 200);
        } catch (Exception $ex) {
            Log::error($ex->getMessage());
            return response()->json(['message' => 'An unexpected error occurred'], 500);
        }
    }

    public function destroy($id)
    {
        DB::beginTransaction();

        try {

            $post = Blog::with(['postTags', 'comment'])->find($id);

            if (!$post) {
                return response()->json(['message' => 'Post not found'], 404);
            }

            // delete featured image if exists
            if (!$post->featured_image && Storage::disk('public')->exists($post->featured_image)) {
                Storage::disk('public')->delete($post->featured_image);
            }

            // Delete associated tags
            if ($post->postTags()->exists()) {
                $post->postTags()->delete();
            }

            // Delete associated comments
            if ($post->comment()->exists()) {
                $post->comment()->delete();
            }

            // Delete the pos
            $post->delete();

            DB::commit();

            return response()->json(['message' => 'Post deleted successfullly'], 200);
        } catch (Exception $ex) {
            DB::rollBack();
            Log::error($ex->getMessage() . 'on line: ' . $ex->getLine());
            return response()->json(['message' => 'An unexpected error occurred'], 500);
        }
    }

    public function fetchComments()
    {
        try {
            $comments = Comment::orderBy('id', 'DESC')->paginate(10);

            if (!$comments->isNotEmpty()) {
                return response()->json(['message' => 'No Commments Found']);
            }

            return response()->json($comments, 200);
        } catch (Exception $ex) {
            Log::error($ex->getMessage());
            return response()->json(['message' => 'An unexpected error occurred'], 500);
        }
    }

    public function approveComment($id)
    {
        DB::beginTransaction();

        try {
            $comment = Comment::find($id);

            if (!$comment) {
                return response()->json(['message' => 'Comment not found!'], 404);
            }

            if ($comment->status === 'approved') {
                return response()->json(['message' => 'You cannot approve this comment again'], 409);
            }
            // Only update if it's not already approved (optional check)
            $comment->update(['status' => 'approved']);

            DB::commit();

            return response()->json(['message' => 'Comment approved successfully'], 200);
        } catch (Exception $ex) {
            DB::rollBack();
            Log::error('Approve Comment Error: ' . $ex->getMessage());

            return response()->json([
                'message' => 'An unexpected error occurred'
            ], 500);
        }
    }


    public function storeComment(Request $request, $slug)
    {
        $request->validate([
            'comment' => ['required', 'string'],
            'comment_name' => ['required', 'string'],
            'comment_email' => ['required', 'email']
        ], [
            'comment.required' => 'This field is required',
            'comment.string' => 'Invalid input',

            'comment_name.required' => 'This field is required',
            'comment_name.string' => 'Invalid input',

            'comment_email.required' => 'This field is required',
            'comment_email.email' => 'Invalid email format'
        ]);

        DB::beginTransaction();

        try {
            $post = Blog::where('slug', $slug)->first();

            if (!$post) {
                return response()->json(['message' => 'This blog post was not found!'], 404);
            }

            Comment::create([
                'comment' => $request->comment,
                'comment_name' => $request->comment_name,
                'comment_email' => $request->comment_email,
                'post_id' => (string)$post->id,
            ]);

            DB::commit();

            return response()->json(['message' => 'Success! Your comment is waiting for approval'], 200);
        } catch (Exception $ex) {
            DB::rollBack();
            Log::error($ex->getMessage());
            return response()->json(['message' => 'An unexpected error occurred'], 500);
        }
    }
}
