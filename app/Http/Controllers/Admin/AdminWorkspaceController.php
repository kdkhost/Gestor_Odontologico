<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Support\AdminModuleRegistry;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class AdminWorkspaceController extends Controller
{
    public function __invoke(Request $request, string $slug): View|RedirectResponse
    {
        $module = AdminModuleRegistry::find($slug, $request->user());

        if (! $module) {
            if (AdminModuleRegistry::findAny($slug)) {
                throw new AccessDeniedHttpException;
            }

            throw new NotFoundHttpException;
        }

        if (! AdminModuleRegistry::isEmbedded($module)) {
            return redirect()->route(
                AdminModuleRegistry::routeName($module),
                AdminModuleRegistry::routeParameters($module),
            );
        }

        return view('admin.workspace', [
            'module' => $module,
            'iframeUrl' => $module['iframe_url'],
        ]);
    }
}
