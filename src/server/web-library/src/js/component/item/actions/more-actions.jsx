import PropTypes from 'prop-types';
import React, { memo, useCallback, useState } from 'react';
import { default as Dropdown } from 'reactstrap/lib/Dropdown';
import { default as DropdownToggle } from 'reactstrap/lib/DropdownToggle';
import { default as DropdownMenu } from 'reactstrap/lib/DropdownMenu';
import { default as DropdownItem } from 'reactstrap/lib/DropdownItem';
import { useDispatch, useSelector } from 'react-redux';

import Icon from '../../ui/icon';
import { cleanDOI, cleanURL, get, getDOIURL, openDelayedURL } from '../../../utils';
import { currentGoToSubscribeUrl, tryGetBestAttachmentURL } from '../../../actions';
import { useItemActionHandlers } from '../../../hooks';

const MoreActionsItems = ({ divider = false }) => {
	const dispatch = useDispatch();
	const isReadOnly = useSelector(state => (state.config.libraries.find(l => l.key === state.current.libraryKey) || {}).isReadOnly);
	const item = useSelector(state => get(state, ['libraries', state.current.libraryKey, 'items', state.current.itemKey]));
	const itemsSource = useSelector(state => state.current.itemsSource);
	const { handleDuplicate } = useItemActionHandlers();

	const attachment = get(item, [Symbol.for('links'), 'attachment'], null);
	const isViewFile = attachment !== null;
	const url = item && item.url ? cleanURL(item.url, true) : null;
	const doi = item && item.DOI ? cleanDOI(item.DOI) : null;
	const isViewOnline = !isViewFile && (url || doi);
	const canDuplicate = !isReadOnly && item && (itemsSource === 'collection' || itemsSource === 'top');

	const handleViewFileClick = useCallback(() => {
		openDelayedURL(dispatch(tryGetBestAttachmentURL(item.key)));
	}, [dispatch, item]);

	const handleViewOnlineClick = useCallback(() => {
		if(url) {
			window.open(url);
		} else if(doi) {
			window.open(getDOIURL(doi));
		}
	}, [doi, url]);

	return (
		<React.Fragment>
			{ isViewFile && (
				<DropdownItem onClick={ handleViewFileClick }>
					View { attachment.attachmentType === 'application/pdf' ? 'PDF' : 'File' }
				</DropdownItem>
			) }
			{ isViewOnline && (
			<DropdownItem onClick={ handleViewOnlineClick }>
				View Online
			</DropdownItem>
			) }
			{ canDuplicate && (
			<DropdownItem onClick={ handleDuplicate }>
				Duplicate Item
			</DropdownItem>
			) }
			{ divider && (canDuplicate || isViewFile || isViewOnline) && <DropdownItem divider/> }
		</React.Fragment>
	);
}

const MoreActionsDropdownDesktop = memo(props => {
	const { tabIndex, onFocusNext, onFocusPrev } = props;
	const dispatch = useDispatch();
	const [isOpen, setIsOpen] = useState(false);

	const handleToggleDropdown = useCallback(() => {
		setIsOpen(!isOpen);
	}, [isOpen]);

	const handleKeyDown = useCallback(ev => {
		if(ev.target !== ev.currentTarget) {
			return;
		}

		if(ev.key === 'ArrowRight') {
			onFocusNext(ev);
		} else if(ev.key === 'ArrowLeft') {
			onFocusPrev(ev);
		}
	}, [onFocusNext, onFocusPrev]);

	const handleSubscribeClick = useCallback(() => {
		dispatch(currentGoToSubscribeUrl());
	}, [dispatch]);

	return (
		<Dropdown
			className="new-item-selector"
			isOpen={ isOpen }
			toggle={ handleToggleDropdown }
		>
			<DropdownToggle
				className="btn-icon dropdown-toggle"
				color={ null }
				onKeyDown={ handleKeyDown }
				tabIndex={ tabIndex }
				title="New Item"
			>
				<Icon type={ '16/options' } width="16" height="16" />
			</DropdownToggle>
			{
				// For performance reasons MoreActionsMenu is only mounted when "more actions" is
				// open otherwise it would re-render every time item selection is changed adding
				// unnecesary overhead
				isOpen && (
					<DropdownMenu>
						<MoreActionsItems divider />
						<DropdownItem onClick={ handleSubscribeClick }>
							Subscribe to Feed
						</DropdownItem>
					</DropdownMenu>
				)
			}
		</Dropdown>
	);
});

MoreActionsDropdownDesktop.propTypes = {
	onFocusNext: PropTypes.func,
	onFocusPrev: PropTypes.func,
	tabIndex: PropTypes.number,
};

MoreActionsDropdownDesktop.displayName = 'MoreActionsDropdownDesktop';

const MoreActionsDropdownTouch = memo(() => {
	const item = useSelector(state => get(state, ['libraries', state.current.libraryKey, 'items', state.current.itemKey]));
	const itemsSource = useSelector(state => state.current.itemsSource);
	const isReadOnly = useSelector(state => (state.config.libraries.find(l => l.key === state.current.libraryKey) || {}).isReadOnly);

	const attachment = get(item, [Symbol.for('links'), 'attachment'], null);
	const isViewFile = attachment !== null;
	const url = item && item.url ? cleanURL(item.url, true) : null;
	const doi = item && item.DOI ? cleanDOI(item.DOI) : null;
	const isViewOnline = !isViewFile && (url || doi);
	const canDuplicate = !isReadOnly && item && (itemsSource === 'collection' || itemsSource === 'top');
	const hasAnyAction = isViewFile|| isViewOnline || canDuplicate;

	const [isOpen, setIsOpen] = useState(false);

	const handleDropdownToggle = useCallback(() => {
		setIsOpen(!isOpen);
	}, [isOpen]);

	return (
		<Dropdown
			isOpen={ isOpen }
			toggle={ handleDropdownToggle }
		>
			<DropdownToggle
				disabled={ !hasAnyAction }
				color={ null }
				className="btn-link btn-icon dropdown-toggle item-actions-touch"
			>
				<Icon
					type="24/options"
					symbol={ isOpen ? 'options-block' : 'options' }
					width="24"
					height="24"
				/>
			</DropdownToggle>
			{ hasAnyAction && ( <DropdownMenu right>
				<MoreActionsItems />
			</DropdownMenu> ) }
		</Dropdown>
	)
});

MoreActionsDropdownTouch.displayName = 'MoreActionsDropdownTouch';

export { MoreActionsDropdownDesktop, MoreActionsItems, MoreActionsDropdownTouch };
