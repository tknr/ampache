<?php
/*
 * vim:set softtabstop=4 shiftwidth=4 expandtab:
 *
 * LICENSE: GNU Affero General Public License, version 3 (AGPL-3.0-or-later)
 * Copyright 2001 - 2022 Ampache.org
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 *
 */

declare(strict_types=0);

namespace Ampache\Module\Application\Preferences;

use Ampache\Config\ConfigContainerInterface;
use Ampache\Module\Application\ApplicationActionInterface;
use Ampache\Module\Application\Exception\AccessDeniedException;
use Ampache\Module\Authorization\AccessLevelEnum;
use Ampache\Module\Authorization\GuiGatekeeperInterface;
use Ampache\Module\System\Core;
use Ampache\Module\System\PreferencesFromRequestUpdaterInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Teapot\StatusCode;

final class AdminUpdatePreferencesAction implements ApplicationActionInterface
{
    public const REQUEST_KEY = 'admin_update_preferences';

    private PreferencesFromRequestUpdaterInterface $preferencesFromRequestUpdater;

    private ResponseFactoryInterface $responseFactory;

    private ConfigContainerInterface $configContainer;

    public function __construct(
        PreferencesFromRequestUpdaterInterface $preferencesFromRequestUpdater,
        ResponseFactoryInterface $responseFactory,
        ConfigContainerInterface $configContainer
    ) {
        $this->preferencesFromRequestUpdater = $preferencesFromRequestUpdater;
        $this->responseFactory               = $responseFactory;
        $this->configContainer               = $configContainer;
    }

    public function run(ServerRequestInterface $request, GuiGatekeeperInterface $gatekeeper): ?ResponseInterface
    {
        if (
            $gatekeeper->mayAccess(AccessLevelEnum::TYPE_INTERFACE, AccessLevelEnum::LEVEL_ADMIN) === false ||
            !Core::form_verify('update_preference')
        ) {
            throw new AccessDeniedException();
        }

        $this->preferencesFromRequestUpdater->update((int) Core::get_post('user_id'));

        return $this->responseFactory
            ->createResponse(StatusCode::FOUND)
            ->withHeader(
                'Location',
                sprintf(
                    '%s/admin/users.php?action=show_preferences&user_id=%s',
                    $this->configContainer->getWebPath(),
                    scrub_out(Core::get_post('user_id'))
                )
            );
    }
}
