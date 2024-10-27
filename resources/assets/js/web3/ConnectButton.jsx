import React, { useEffect, useState } from "react";
import { createWeb3Modal } from '@web3modal/wagmi'
// import { bsc, bscTestnet } from "viem/chains";
import { mainnet, bsc } from '@wagmi/core/chains'
import { reconnect, getAccount, disconnect, getBalance, writeContract, createConfig, http } from "@wagmi/core";
import { abi } from "./abi";
import { walletConnect, coinbaseWallet, injected } from '@wagmi/connectors'

const projectURL = process.env.REACT_APP_PROJECT_URL;
const projectId = process.env.REACT_APP_PROJECT_ID;
const tokenAddress = process.env.REACT_APP_AGII_BSC_TOKEN_ADDRESS;
// const tokenAddress = process.env.REACT_APP_TEST_TOKEN_ADDRESS;//testToken
const targetAddress = process.env.REACT_APP_BSC_TARGET_ADDRESS;
const apiCMCKey = process.env.REACT_APP_CMC_API_KEY;
const apiCMCEndpoint = 'https://pro-api.coinmarketcap.com/v2/cryptocurrency/quotes/latest?symbol=AGII';

const apiCGKey = process.env.REACT_APP_CG_API_KEY;
const apiCGEndpoint = `https://pro-api.coingecko.com/api/v3/onchain/simple/networks/bsc/token_price/${tokenAddress}`;
const metadata = {
  name: 'Atua',
  description: 'Atua AI',
  url: projectURL,
  icons: ['https://avatars.githubusercontent.com/u/37784886'],
};

const chains = [bsc];
const config = createConfig({
  chains,
  transports: {
    [bsc.id]: http(),
  },
  connectors: [
    coinbaseWallet({ chains, appName: 'Atua AI Dapp' }),
    walletConnect({ projectId, metadata, showQrModal: false }),
    injected({ chains, shimDisconnect: true }),
  ],
  autoConnect: true,
})

reconnect(config);

export const web3Modal = createWeb3Modal({
  wagmiConfig: config,
  projectId,
  themeMode: 'dark',
  chainId: bsc.id,
});

function truncateAddress(address, startLength = 4, endLength = 6) {
  if (!address || address.length < startLength + endLength) {
    return address;
  }

  const start = address.substring(0, startLength);
  const end = address.substring(address.length - endLength);
  return `${start}...${end}`;
}

function setCookie(name, value, days) {
  let expires = "";
  if (days) {
    const date = new Date();
    date.setTime(date.getTime() + (days * 24 * 60 * 60 * 1000));
    expires = "; expires=" + date.toUTCString();
  }
  document.cookie = name + "=" + (value || "") + expires + "; path=/";
}

function setJwtToken(token) {
  localStorage.setItem('jwt', token);
  setCookie('user', token, 7); // Sets a cookie with a 7 day expiry
}

function getCookie(name) {
  const value = `; ${document.cookie}`;
  const parts = value.split(`; ${name}=`);
  if (parts.length === 2) return parts.pop().split(';').shift();
}

function getJwtToken() {
  return localStorage.getItem('jwt') || getCookie('user');
}

const handleConnect = async () => {
  try {
    await web3Modal.open();
    // console.log("Wallet connected successfully!");
  } catch (error) {
    console.log("Failed to connect wallet:", error);
  }
};

const handleLogin = async (walletAddress) => {
  const data = new URLSearchParams({
    wallet_address: walletAddress,
    first_name: truncateAddress(walletAddress),
  });

  try {
    const response = await fetch('/api/auth/signup', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/x-www-form-urlencoded',
      },
      body: data.toString()
    });

    if (!response.ok) {
      console.log(`HTTP error! status: ${response.status}`);
      return;
    }
    const result = await response.json();
    const jwtToken = result.jwt

    if (result?.isNew) {
      try {
        var ress = await fetch('/api/billing/plans', {
          method: 'GET',
          headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
            'Authorization': 'Bearer ' + jwtToken,
            'Accept': 'application/json',
          }
        });
        ress = await ress.json();
        var freeplan = ress?.data?.filter(item => (String(item.title).toLowerCase() == "free" || parseInt(item.price) == 0) && item.is_featured)

        if (freeplan.length == 0) {
          window.location.href = '/app';
          return;
        }
        const udata = new URLSearchParams({
          id: freeplan[0]['id'],
          tx: 'free-plan',
        });

        try {
          await fetch('/api/billing/checkout', {
            method: 'POST',
            headers: {
              'Content-Type': 'application/x-www-form-urlencoded',
              'Authorization': 'Bearer ' + jwtToken,
              'Accept': 'application/json',
              'X-Workspace-Id': result.workid,
            },
            body: udata.toString()
          });
        } catch (error) {
          console.log("checkout error -> ", error)
          return;
        }

      } catch (error) {
        console.log("fetch error => ", error)
        return;
      }
    }

    setJwtToken(jwtToken);
    if (!window.location.pathname.includes("tua")) window.location.href = '/app';
    // window.location.reload();
    // console.log(JSON.stringify(result));
  } catch (error) {
    console.log(error);
  }
};

export const ConnectButton = () => {
  const [account, setAccount] = useState(null);
  useEffect(() => {
    const updateAccount = () => {
      const currentAccount = getAccount(config);
      if (currentAccount?.isConnected && currentAccount?.address) {
        setAccount(currentAccount.address);
        handleLogin(currentAccount.address);
      } else if (currentAccount?.isDisconnected) {
        setAccount(null);
        // window.location.href = '/logout';
      } else {
        setAccount(null);
      }
    };

    web3Modal.subscribeState(updateAccount);
    updateAccount();

    return () => {
      web3Modal.unsubscribeState(updateAccount);
    };
  }, []);

  return (
    <div onClick={handleConnect} className="w-full">
      {account ? truncateAddress(account) : "Wallet Connect"}
    </div>
  );
};

export const DisconnectButtion = () => {
  const handleLogout = () => {
    web3Modal.close();
    disconnect(config);
    window.location = '/logout';
  }

  return (
    <button onClick={handleLogout} className="text-left">
      Logout
    </button>
  )
}

export const OpenWeb3ModalButton = ({ title }) => {
  const handleClick = async () => {
    try {
      await web3Modal.open();
    } catch (error) {
      console.log('Failed to open the web3 modal:', error);
    }
  };
  return (
    <div className="w-full" onClick={handleClick}>
      {title}
    </div>
  )
}

export const PurchaseButton = ({ workspaceId, subscriptionId, planPrice, planId, flag = true, planDetailId }) => {
  const [title, setTitle] = useState('Loading...');
  const [loading, setLoading] = useState(true);
  const [transferPending, setTransferPending] = useState(false);
  const [tokenPrice, setTokenPrice] = useState(0);
  const token = getJwtToken();

  useEffect(() => {
    const updateAccount = async () => {
      const currentAccount = getAccount(config);
      // console.log(currentAccount);
      if (currentAccount?.isConnecting && currentAccount?.address) {
        setTitle('Loading...');
        setLoading(true);
      } else if (currentAccount?.address && currentAccount?.isConnected) {
        setTitle('Buy');
        setLoading(false);
      } else {
        setTitle('Please check wallet connection');
        setLoading(true);
      }
    };

    const fetchTokenPrice = async () => {
      try {
        // const response = await fetch(apiCMCEndpoint, {
        //   method: 'GET',
        //   headers: {
        //     'Accepts': 'application/json',
        //     'X-CMC_PRO_API_KEY': apiCMCKey
        //   },
        //   //  mode: 'no-cors'
        // });

        const response = await fetch(apiCGEndpoint, {
          method: 'GET',
          headers: {
            'Accepts': 'application/json',
            'x-cg-pro-api-key': apiCGKey
          },
        });
        const data = await response.json();
        const price = data.data.attributes.token_prices[tokenAddress];
        setTokenPrice(price);
      } catch (error) {
        console.error('Error fetching token price:', error);
      }
    };

    fetchTokenPrice();
    web3Modal.subscribeState(updateAccount);
    updateAccount();

    return () => {
      web3Modal.unsubscribeState(updateAccount);
    };
  }, []);

  const transfer = async (tokenCount) => {
    const account = getAccount(config);
    if (account?.isConnected && account?.address) {
      try {
        setLoading(true);
        setTransferPending(true);
        const balanceData = await getBalance(config, {
          address: account.address,
          chainId: bsc.id,
          token: tokenAddress
        });

        const tokenCountInWei = BigInt(Math.floor(tokenCount * 10 ** 18));
        // const tokenCountInWei = BigInt(Math.floor(10 * 10 ** 18));
        if (tokenCountInWei <= BigInt(balanceData.value)) {
          const hash = await writeContract(config, {
            abi: abi,
            address: tokenAddress,
            functionName: 'transfer',
            args: [
              targetAddress,
              (tokenCountInWei).toString(),
            ],
            chainId: bsc.id,
          });
          // console.log('hash', hash);
          return hash;
        } else {
          alert('Insufficient balance.');
          return null;
        }
      } catch (error) {
        console.log('Failed to fetch balance:', error);
        return null;
      } finally {
        setLoading(false);
        setTransferPending(false);
      }
    } else {
      alert('Please check your wallet connection');
    }
  };

  const handlePurchase = async () => {
    if (tokenPrice === 0) {
      alert('Unable to fetch token price.');
      return;
    }
    const neededTokens = planPrice / Number(tokenPrice);
    const hash = await transfer(neededTokens);
    if (!hash) return;

    const data = new URLSearchParams({
      id: planId,
      tx: hash,
    });

    try {
      const response = await fetch('/api/billing/checkout', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/x-www-form-urlencoded',
          'Authorization': 'Bearer ' + token,
          'Accept': 'application/json',
          'X-Workspace-Id': workspaceId,
        },
        body: data.toString()
      });

      let result = await response.json();
      // console.log('result', result.is_fulfilled);
      if (result.is_fulfilled) {
        alert('Transaction successful');
        window.location.href = '/app';
      } else {
        console.log('Navigation Condition Not Met: result.is_fulfilled is not true');
      }
    } catch (error) {
      console.log('Error during purchase:', error);
    }
  };

  const neededTokens = (planPrice / Number(tokenPrice)).toFixed(2);

  return (
    <div className="w-full flex flex-col gap-2">
      <div className={flag ? '' : 'hidden'} >Price per AGII token: {!tokenPrice ? 'loading... ' : ` ${tokenPrice} (USD)`}</div>
      <div className={flag ? '' : 'hidden'}>Total Needed Tokens: {!neededTokens ? 'loading... ' : ` ${neededTokens}`}</div>
      {
        subscriptionId == planDetailId ?
          <button
            className="w-full button"
            disabled={true}
          >
            Already Purchased
          </button> :
          <button
            className="w-full button"
            onClick={!loading && !transferPending ? handlePurchase : null}
            disabled={loading || transferPending}
          >
            {title}
          </button>
      }
    </div>
  );
};
export const ConnectButton2 = () => {
  const [loading, setLoading] = useState(false);
  const [title, setTitle] = useState('Connect Wallet');
  useEffect(() => {
    const updateAccount = () => {
      const currentAccount = getAccount(config);
      // console.log(currentAccount);
      if (currentAccount?.isConnected && currentAccount?.address) {
        setTitle(truncateAddress(currentAccount.address));
        setLoading(false);
      } else if (currentAccount?.isConnecting && currentAccount?.address) {
        setTitle('Connecting...');
        setLoading(true);
      }
      else {
        setTitle('Connect Wallet');
        setLoading(false);
      }
    };
    setLoading(true);
    web3Modal.subscribeState(updateAccount);
    updateAccount();

    return () => {
      web3Modal.unsubscribeState(updateAccount);
    };
  }, []);


  const handleConnect = async () => {
    try {
      await web3Modal.open();
    } catch (error) {
      console.log("Failed to connect wallet:", error);
    }
  };

  return (
    <button onClick={handleConnect} className="w-full h-full border-none">
      {title}
    </button>
  );
};

export const CustomPlans = ({ workspaceId, subscriptionId }) => {
  const [isLoading, setLoading] = useState(false);
  const [CPlans, setCPlans] = useState([])
  const [tokenPrice, setTokenPrice] = useState(0)

  useEffect(() => {

    const getTokenPrice = async () => {
      try {

        const response = await fetch(apiCGEndpoint, {
          method: 'GET',
          headers: {
            'Accepts': 'application/json',
            'x-cg-pro-api-key': apiCGKey
          },
          //  mode: 'no-cors'
        });
        const data = await response.json();
        const price = data.data.attributes.token_prices[tokenAddress];
        setTokenPrice(price);
      } catch (error) {
        console.error('Error fetching token price:', error);
      }
    };

    getTokenPrice();

    getPlans();
  }, []);

  const getPlans = async () => {
    setLoading(true);
    const token = getJwtToken();

    try {

      const response = await fetch('/api/billing/plans', {
        method: 'GET',
        headers: {
          'Content-Type': 'application/x-www-form-urlencoded',
          'Authorization': 'Bearer ' + token,
          'Accept': 'application/json',
        }
      });

      const result = await response.json();
      setCPlans(result.data.filter((item) => (parseInt(item.price) > 0 || String(item.title).toLowerCase() != "free")))

      setLoading(false);
    } catch (error) {
      console.log('Error during purchase:', error);
      setLoading(false);
    }
  }

  const computeTotalTokens = (planPrice) => {
    const neededTokens = ((planPrice / 100) / Number(tokenPrice)).toFixed(2);
    return neededTokens;
  }

  return (
    <div className="w-full">
      <ul className={`flex flex-col gap-1 ${CPlans.length === 0 ? 'hidden' : ''}`}>
        {/* Initial Loading States */}
        {[...Array(2)].map((_, i) => (
          <li key={i} className="hidden grid-cols-3 gap-8 p-8 box">
            <div>
              <div className="w-32 h-6 loading"></div>
              <div className="w-48 h-5 mt-1 loading"></div>
            </div>
            <div>
              <div className="w-32 h-6 loading"></div>
              <div className="w-48 h-5 mt-1 loading"></div>
            </div>
            <div className="flex flex-col items-end">
              <div className="w-32 h-6 loading"></div>
              <div className="w-48 h-5 mt-1 loading"></div>
            </div>
          </li>
        ))}

        <li class="relative grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 ld:gap-6 box">

          {!isLoading && CPlans.map((plan, index) => (
            <div
              key={index}
              className={`enter p-6 rounded-3xl bg-contrast-secondary flex border-0`}
            >
              <div className="w-full bg-contrast-primary rounded-2xl h-full flex flex-col justify-between">
                <div className="w-full">
                  <h3 className="mt-1 text-3xl">{plan.title}<span className="text-tertiary text-lg text-content-dimmed" style={{ fontSize: "1.2rem" }}> / {plan.billing_cycle == 'lifetime' ? 'One time' : plan.billing_cycle}</span></h3>

                  {<p className="text-tertiary text-md text-content-dimmed">{plan.description}</p>}

                  <div className="mt-3">
                    <span className="text-xl">{computeTotalTokens(plan.price)} AGII</span>
                    {/* <span className="text-tertiary text-md text-content-dimmed">/{plan.billing_cycle}</span> */}
                  </div>
                  {/* <div className="w-full">
                    <span className="ext-tertiary text-md text-content-dimmed">1 AGII token: {(tokenPrice)} (USD)</span>
                  </div> */}

                  <ul className="flex flex-col gap-2 mt-4 mb-4">
                    <li className={`flex items-center gap-2 font-semibold ${plan.config.writer.is_enabled ? '' : 'text-tertiary'}`}>
                      <i className={`text-2xl ${plan.config.writer.is_enabled ? 'ti ti-square-rounded-check-filled' : 'ti ti-square-rounded-x'}`}></i>
                      Writer
                      {plan.config.writer.is_enabled && (
                        <div className="p-0.5 border-2 rounded-lg bg-contrast-secondary font-bold relative text-accent">
                          <span className="rounded-md py-1 px-2 text-xs">
                            {plan.config.writer.model}
                          </span>
                        </div>
                      )}
                    </li>

                    <li className={`flex items-center gap-2 font-semibold ${plan.config.coder.is_enabled ? '' : 'text-tertiary'}`}>
                      <i className={`text-2xl ${plan.config.coder.is_enabled ? 'ti ti-square-rounded-check-filled' : 'ti ti-square-rounded-x'}`}></i>
                      Coding Assistant
                      {plan.config.coder.is_enabled && (
                        <div className="p-0.5 border-2 rounded-lg bg-contrast-secondary font-bold relative text-accent">
                          <span className="rounded-md py-1 px-2 text-xs">
                            {plan.config.coder.model}
                          </span>
                        </div>
                      )}
                    </li>

                    <li className={`flex items-center gap-2 font-semibold ${plan.config.imagine.is_enabled ? '' : 'text-tertiary'}`}>
                      <i className={`text-2xl ${plan.config.imagine.is_enabled ? 'ti ti-square-rounded-check-filled' : 'ti ti-square-rounded-x'}`}></i>
                      Image generator
                    </li>

                    <li className={`flex items-center gap-2 font-semibold ${plan.config.transcriber.is_enabled ? '' : 'text-tertiary'}`}>
                      <i className={`text-2xl ${plan.config.transcriber.is_enabled ? 'ti ti-square-rounded-check-filled' : 'ti ti-square-rounded-x'}`}></i>
                      Transcriber
                    </li>

                    <li className={`flex items-center gap-2 font-semibold ${plan.config.voiceover.is_enabled ? '' : 'text-tertiary'}`}>
                      <i className={`text-2xl ${plan.config.voiceover.is_enabled ? 'ti ti-square-rounded-check-filled' : 'ti ti-square-rounded-x'}`}></i>
                      Voiceover
                    </li>

                    <li className="flex items-center gap-2 font-semibold">
                      <i className={`text-2xl ${plan.credit_count === 0 && plan.credit_count !== null ? 'ti ti-square-rounded-x text-tertiary' : 'ti ti-square-rounded-check-filled'}`}></i>
                      {plan.credit_count === 0 && plan.credit_count !== null ? 'Access to templates is disabled' : 'Access to all templates'}
                    </li>

                    <li className="flex items-center gap-2 font-semibold">
                      <i className="text-2xl ti ti-square-rounded-check-filled"></i>
                      {plan.credit_count} credits
                    </li>

                    <li className="flex items-center gap-2 font-semibold">
                      <i className="text-2xl ti ti-square-rounded-check-filled"></i>
                      {plan.price > 0 ? 'Priority support' : 'Basic support'}
                    </li>

                    {plan.feature_list.map((li, index) => (
                      <li key={index} className="flex items-center gap-2 font-semibold">
                        <i className={`text-2xl ${li.is_included ? 'ti ti-square-rounded-check-filled' : 'ti ti-square-rounded-x text-tertiary'}`}></i>
                        {li.title}
                      </li>
                    ))}

                  </ul>

                </div>
                <PurchaseButton workspaceId={workspaceId} subscriptionId={subscriptionId} planPrice={plan.price / 100} planId={plan.id} flag={false} planDetailId={plan.snapshot.id} />
              </div>
            </div>
          ))}

        </li>
      </ul>
    </div>
  )
}