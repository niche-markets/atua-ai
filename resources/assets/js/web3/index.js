import React, { useEffect } from "react";
import ReactDOM from "react-dom";
import { ConnectButton, DisconnectButtion, OpenWeb3ModalButton, PurchaseButton, ConnectButton2, CustomPlans } from "./ConnectButton.jsx";

const getDatasetAttributes = (element) => {
  return {
    workspaceId: element.dataset.workspace,
    planPrice: element.dataset.planprice,
    planId: element.dataset.planid,
  };
};

const connectButtonElement = document.getElementById('btn-connect');

if (connectButtonElement) {
  ReactDOM.render(<ConnectButton />, connectButtonElement);
}

const logoutButtionElement = document.getElementById('btn-logout');

if (logoutButtionElement) {
  ReactDOM.render(<DisconnectButtion />, logoutButtionElement);
}

const openWeb3ModalElement = document.getElementById('btn-open-web3modal');

if (openWeb3ModalElement) {
  ReactDOM.render(<ConnectButton2 />, openWeb3ModalElement);
}

const walletConnectSigninElement = document.getElementById('btn-connect-2');

if (walletConnectSigninElement) {
  ReactDOM.render(<ConnectButton />, walletConnectSigninElement)
}

const purchaseButtonElement = document.getElementById('btn-purchase');

if (purchaseButtonElement) {
  const datasetAttributes = getDatasetAttributes(purchaseButtonElement);
  ReactDOM.render(<PurchaseButton {...datasetAttributes} />, purchaseButtonElement);
}


const customPlansElement = document.getElementById('custom-plans');

if (customPlansElement) {
  const workspaceId = customPlansElement.dataset.workspace;
  const subscriptionId = customPlansElement.dataset.subscription;
  ReactDOM.render(<CustomPlans workspaceId={workspaceId} subscriptionId={subscriptionId} />, customPlansElement);
}

